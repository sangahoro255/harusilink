<?php
$pageUrl = function (int $p) use ($q): string {
    $query = http_build_query(array_filter(['q' => $q, 'page' => $p > 1 ? $p : null]));
    return url('/admin/users') . ($query !== '' ? '?' . $query : '');
};
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(t('admin.users.meta_title')) ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body>
<?php view('partials/admin_header', ['active' => 'users']); ?>

<main class="admin-main">
  <h1><?= e(t('admin.users.title')) ?></h1>
  <p class="muted"><?= e(t('admin.users.total', ['count' => $total])) ?></p>

  <?php if ($flash): ?>
    <div class="alert alert-success"><?= e(t($flash)) ?></div>
  <?php endif; ?>

  <form method="get" action="<?= e(url('/admin/users')) ?>" class="search-bar">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('admin.users.search_placeholder')) ?>">
    <button class="btn" type="submit"><?= e(t('admin.users.search_button')) ?></button>
  </form>

  <?php if (!$rows): ?>
    <div class="empty"><?= e(t('admin.users.empty')) ?></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th><?= e(t('admin.users.col.name')) ?></th>
            <th><?= e(t('admin.users.col.email')) ?></th>
            <th><?= e(t('admin.users.col.phone')) ?></th>
            <th><?= e(t('admin.users.col.weddings')) ?></th>
            <th><?= e(t('admin.users.col.joined')) ?></th>
            <th><?= e(t('admin.users.col.last_login')) ?></th>
            <th><?= e(t('admin.users.col.status')) ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <?php $isActive = $r['status'] === 'active'; ?>
            <tr>
              <td data-label="<?= e(t('admin.users.col.name')) ?>"><?= e($r['name']) ?></td>
              <td data-label="<?= e(t('admin.users.col.email')) ?>"><?= e($r['email']) ?></td>
              <td data-label="<?= e(t('admin.users.col.phone')) ?>"><?= e($r['phone'] ?: '—') ?></td>
              <td data-label="<?= e(t('admin.users.col.weddings')) ?>"><?= e((string) $r['weddings_count']) ?></td>
              <td data-label="<?= e(t('admin.users.col.joined')) ?>"><?= e(substr($r['created_at'], 0, 10)) ?></td>
              <td data-label="<?= e(t('admin.users.col.last_login')) ?>"><?= e($r['last_login_at'] ? substr($r['last_login_at'], 0, 16) : t('admin.users.never')) ?></td>
              <td data-label="<?= e(t('admin.users.col.status')) ?>">
                <span class="badge badge-<?= $isActive ? 'active' : 'suspended' ?>">
                  <?= e(t($isActive ? 'admin.users.status.active' : 'admin.users.status.suspended')) ?>
                </span>
              </td>
              <td class="row-action">
                <form method="post" action="<?= e(url('/admin/users/status')) ?>"
                  <?php if ($isActive): ?>onsubmit="return confirm(<?= e(json_encode(t('admin.users.confirm_suspend'), JSON_UNESCAPED_UNICODE)) ?>)"<?php endif; ?>>
                  <?= csrf_field() ?>
                  <input type="hidden" name="user_id" value="<?= e((string) $r['id']) ?>">
                  <input type="hidden" name="status" value="<?= $isActive ? 'suspended' : 'active' ?>">
                  <input type="hidden" name="q" value="<?= e($q) ?>">
                  <input type="hidden" name="page" value="<?= e((string) $page) ?>">
                  <button type="submit" class="btn-small <?= $isActive ? 'btn-danger' : 'btn-ok' ?>">
                    <?= e(t($isActive ? 'admin.users.btn.suspend' : 'admin.users.btn.activate')) ?>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="pager">
      <?php if ($page > 1): ?>
        <a class="btn-outline" href="<?= e($pageUrl($page - 1)) ?>"><?= e(t('admin.users.prev')) ?></a>
      <?php else: ?>
        <span></span>
      <?php endif; ?>

      <span class="muted"><?= e(t('admin.users.page', ['page' => $page, 'pages' => $pages])) ?></span>

      <?php if ($page < $pages): ?>
        <a class="btn-outline" href="<?= e($pageUrl($page + 1)) ?>"><?= e(t('admin.users.next')) ?></a>
      <?php else: ?>
        <span></span>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</main>
</body>
</html>