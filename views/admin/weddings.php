<?php
$pageUrl = function (int $p) use ($q, $status): string {
    $query = http_build_query(array_filter([
        'q'      => $q,
        'status' => $status,
        'page'   => $p > 1 ? $p : null,
    ]));
    return url('/admin/weddings') . ($query !== '' ? '?' . $query : '');
};
$statuses = ['draft', 'active', 'completed', 'archived'];
$badge = ['draft' => 'neutral', 'active' => 'active', 'completed' => 'done', 'archived' => 'neutral'];
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(t('admin.weddings.meta_title')) ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body>
<?php view('partials/admin_header', ['active' => 'weddings']); ?>

<main class="admin-main">
  <h1><?= e(t('admin.weddings.title')) ?></h1>
  <p class="muted"><?= e(t('admin.weddings.total', ['count' => $total])) ?></p>

  <form method="get" action="<?= e(url('/admin/weddings')) ?>" class="filters">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('admin.weddings.search_placeholder')) ?>">
    <select name="status">
      <option value=""><?= e(t('admin.weddings.filter_all')) ?></option>
      <?php foreach ($statuses as $s): ?>
        <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(t('admin.weddings.status.' . $s)) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn" type="submit"><?= e(t('admin.weddings.search_button')) ?></button>
  </form>

  <?php if (!$rows): ?>
    <div class="empty"><?= e(t('admin.weddings.empty')) ?></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th><?= e(t('admin.weddings.col.couple')) ?></th>
            <th><?= e(t('admin.weddings.col.date')) ?></th>
            <th><?= e(t('admin.weddings.col.venue')) ?></th>
            <th><?= e(t('admin.weddings.col.owner')) ?></th>
            <th><?= e(t('admin.weddings.col.status')) ?></th>
            <th><?= e(t('admin.weddings.col.created')) ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td data-label="<?= e(t('admin.weddings.col.couple')) ?>"><?= e($r['bride_name'] . ' & ' . $r['groom_name']) ?></td>
              <td data-label="<?= e(t('admin.weddings.col.date')) ?>"><?= e($r['wedding_date']) ?></td>
              <td data-label="<?= e(t('admin.weddings.col.venue')) ?>"><?= e($r['venue']) ?></td>
              <td data-label="<?= e(t('admin.weddings.col.owner')) ?>"><?= e($r['owner_name']) ?><br><span class="muted"><?= e($r['owner_email']) ?></span></td>
              <td data-label="<?= e(t('admin.weddings.col.status')) ?>">
                <span class="badge badge-<?= e($badge[$r['status']] ?? 'neutral') ?>"><?= e(t('admin.weddings.status.' . $r['status'])) ?></span>
              </td>
              <td data-label="<?= e(t('admin.weddings.col.created')) ?>"><?= e(substr($r['created_at'], 0, 10)) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php view('partials/pager', ['page' => $page, 'pages' => $pages, 'pageUrl' => $pageUrl]); ?>
  <?php endif; ?>
</main>
</body>
</html>