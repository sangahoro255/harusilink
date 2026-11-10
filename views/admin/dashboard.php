<?php
$weddings = $weddings ?? [];
$badge = ['draft' => 'neutral', 'active' => 'active', 'completed' => 'done', 'archived' => 'neutral'];
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(t('dashboard.meta_title')) ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/owner.css')) ?>">
</head>
<body>
<?php 
$headerpartial = (($user['role'] ?? '') === 'super_admin')
    ? 'partials/admin_header'
    : 'partials/owner_header';
view($headerpartial, ['active' => 'dashboard']);
?>


<main class="admin-main">
  <div class="page-head">
    <div>
      <h1><?= e(t('dashboard.welcome', ['name' => $user['name']])) ?></h1>
      <p class="muted"><?= e(t('dashboard.my_weddings')) ?></p>
    </div>
  <?php if (($user['role'] ?? '') !== 'super_admin'): ?>
    <a class="btn-link" href="<?= e(url('/weddings/create')) ?>"><?= e(t('dashboard.new_wedding')) ?></a>
  <?php endif; ?>
  </div>

<?php if (!$weddings): ?>
    <div class="empty">
      <strong><?= e(t('dashboard.empty_title')) ?></strong><br>
      <?= e(t('dashboard.empty_text')) ?>
    </div>
  <?php else: ?>
    <div class="wedding-grid">
      <?php foreach ($weddings as $w): ?>
        <div class="wedding-card">
          <span class="badge badge-<?= e($badge[$w['status']] ?? 'neutral') ?>"><?= e(t('wedding.status.' . $w['status'])) ?></span>
          <h2><?= e($w['bride_name'] . ' & ' . $w['groom_name']) ?></h2>
          <div class="meta"><?= e($w['wedding_date']) ?><?= $w['wedding_time'] ? ' · ' . e(substr($w['wedding_time'], 0, 5)) : '' ?></div>
          <div class="meta"><?= e($w['venue']) ?></div>
          <div class="open">
            <a class="btn-outline" href="<?= e(url('/weddings/' . $w['id'])) ?>"><?= e(t('dashboard.open')) ?></a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
</body>
</html>