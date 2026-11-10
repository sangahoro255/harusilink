<?php
namespace App\Controllers;

use App\Core\Auth;

class AdminController
{
    public function index(): void
    {
        $user = Auth::requireRole('super_admin');
        $pdo = db();

        $stats = [
            'owners'          => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'owner' AND deleted_at IS NULL")->fetchColumn(),
            'suspended'       => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'suspended' AND deleted_at IS NULL")->fetchColumn(),
            'weddings'        => (int) $pdo->query("SELECT COUNT(*) FROM weddings WHERE deleted_at IS NULL")->fetchColumn(),
            'active_weddings' => (int) $pdo->query("SELECT COUNT(*) FROM weddings WHERE status = 'active' AND deleted_at IS NULL")->fetchColumn(),
        ];

        view('admin/dashboard', ['user' => $user, 'stats' => $stats]);
    }

    public function users(): void
    {
        $user = Auth::requireRole('super_admin');
        $pdo = db();

        $q = $this->searchTerm();
        $perPage = 20;

        $where = "role = 'owner' AND deleted_at IS NULL";
        $params = [];

        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where .= ' AND (name LIKE :q1 OR email LIKE :q2)';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
        }

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $where");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        [$page, $pages, $offset] = $this->paginate($total, $perPage);

        $stmt = $pdo->prepare(
            "SELECT id, name, email, phone, status, created_at, last_login_at,
                    (SELECT COUNT(*) FROM weddings w WHERE w.owner_id = users.id AND w.deleted_at IS NULL) AS weddings_count
             FROM users
             WHERE $where
             ORDER BY id DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        view('admin/users', [
            'user'  => $user,
            'rows'  => $stmt->fetchAll(),
            'q'     => $q,
            'page'  => $page,
            'pages' => $pages,
            'total' => $total,
            'flash' => $flash,
        ]);
    }

    public function updateUserStatus(): void
    {
        $admin = Auth::requireRole('super_admin');
        csrf_verify();

        $id = (int) post('user_id');
        $status = post('status');

        $query = http_build_query(array_filter([
            'q'    => mb_substr(post('q'), 0, 100),
            'page' => max(1, (int) post('page')),
        ]));
        $back = '/admin/users' . ($query !== '' ? '?' . $query : '');

        if ($id <= 0 || !in_array($status, ['active', 'suspended'], true)) {
            redirect($back);
        }

        $stmt = db()->prepare(
            "UPDATE users SET status = :status
             WHERE id = :id AND role = 'owner' AND deleted_at IS NULL"
        );
        $stmt->execute([':status' => $status, ':id' => $id]);

        if ($stmt->rowCount() > 0) {
            $this->audit(
                (int) $admin['id'],
                $status === 'suspended' ? 'user.suspend' : 'user.activate',
                'user',
                $id
            );
            $_SESSION['flash'] = 'admin.users.updated';
        }

        redirect($back);
    }

    public function weddings(): void
    {
        $user = Auth::requireRole('super_admin');
        $pdo = db();

        $q = $this->searchTerm();
        $status = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : '';
        if (!in_array($status, ['draft', 'active', 'completed', 'archived'], true)) {
            $status = '';
        }
        $perPage = 20;

        $where = 'w.deleted_at IS NULL';
        $params = [];

        if ($status !== '') {
            $where .= ' AND w.status = :status';
            $params[':status'] = $status;
        }

        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where .= ' AND (w.bride_name LIKE :q1 OR w.groom_name LIKE :q2 OR w.venue LIKE :q3 OR u.email LIKE :q4)';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
            $params[':q4'] = $like;
        }

        $countStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM weddings w JOIN users u ON u.id = w.owner_id WHERE $where"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        [$page, $pages, $offset] = $this->paginate($total, $perPage);

        $stmt = $pdo->prepare(
            "SELECT w.id, w.bride_name, w.groom_name, w.wedding_date, w.venue, w.status, w.created_at,
                    u.name AS owner_name, u.email AS owner_email
             FROM weddings w
             JOIN users u ON u.id = w.owner_id
             WHERE $where
             ORDER BY w.id DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        view('admin/weddings', [
            'user'   => $user,
            'rows'   => $stmt->fetchAll(),
            'q'      => $q,
            'status' => $status,
            'page'   => $page,
            'pages'  => $pages,
            'total'  => $total,
        ]);
    }

    public function activity(): void
    {
        $user = Auth::requireRole('super_admin');
        $pdo = db();
        $perPage = 30;

        $total = (int) $pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
        [$page, $pages, $offset] = $this->paginate($total, $perPage);

        $stmt = $pdo->prepare(
            'SELECT a.id, a.action, a.entity_type, a.entity_id, a.ip_address, a.created_at, u.name AS actor_name
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.id DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        view('admin/activity', [
            'user'  => $user,
            'rows'  => $stmt->fetchAll(),
            'page'  => $page,
            'pages' => $pages,
            'total' => $total,
        ]);
    }

    private function searchTerm(): string
    {
        return isset($_GET['q']) && is_string($_GET['q']) ? mb_substr(trim($_GET['q']), 0, 100) : '';
    }

    private function paginate(int $total, int $perPage): array
    {
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);
        return [$page, $pages, ($page - 1) * $perPage];
    }

    private function audit(int $userId, string $action, string $entityType, int $entityId, array $details = []): void
    {
        $stmt = db()->prepare(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address, details)
             VALUES (:user_id, :action, :entity_type, :entity_id, :ip, :details)'
        );
        $stmt->execute([
            ':user_id'     => $userId,
            ':action'      => $action,
            ':entity_type' => $entityType,
            ':entity_id'   => $entityId,
            ':ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
            ':details'     => $details ? json_encode($details) : null,
        ]);
    }
}