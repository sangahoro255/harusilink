<?php
$pageUrl = function (int $p): string {
    return url('/admin/activity') . ($p > 1 ? '?page=' . $p : '');
};
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(t('admin.activity.meta_title')) ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body>
<?php view('partials/admin_header', ['active' => 'activity']); ?>

<main class="admin-main">
  <h1><?= e(t('admin.activity.title')) ?></h1>
  <p class="muted"><?= e(t('admin.activity.total', ['count' => $total])) ?></p>

  <?php if (!$rows): ?>
    <div class="empty"><?= e(t('admin.activity.empty')) ?></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th><?= e(t('admin.activity.col.time')) ?></th>
            <th><?= e(t('admin.activity.col.actor')) ?></th>
            <th><?= e(t('admin.activity.col.action')) ?></th>
            <th><?= e(t('admin.activity.col.target')) ?></th>
            <th><?= e(t('admin.activity.col.ip')) ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <?php
              $key = 'audit.' . $r['action'];
              $label = t($key);
              if ($label === $key) {
                  $label = $r['action'];
              }
            ?>
            <tr>
              <td data-label="<?= e(t('admin.activity.col.time')) ?>"><?= e(substr($r['created_at'], 0, 16)) ?></td>
              <td data-label="<?= e(t('admin.activity.col.actor')) ?>"><?= e($r['actor_name'] ?: t('admin.activity.system')) ?></td>
              <td data-label="<?= e(t('admin.activity.col.action')) ?>"><?= e($label) ?></td>
              <td data-label="<?= e(t('admin.activity.col.target')) ?>"><?= e($r['entity_type'] ? $r['entity_type'] . ' #' . $r['entity_id'] : '—') ?></td>
              <td data-label="<?= e(t('admin.activity.col.ip')) ?>"><?= e($r['ip_address'] ?: '—') ?></td>
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