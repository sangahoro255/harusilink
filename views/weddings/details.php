<?php
$badge = ['draft' => 'neutral', 'active' => 'active', 'completed' => 'done', 'archived' => 'neutral'];
$couple = $wedding['bride_name'] . ' & ' . $wedding['groom_name'];
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(t('ws.nav.details')) ?> – <?= e($couple) ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/owner.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/workspace.css')) ?>">
</head>
<body>
<?php view('partials/owner_header', ['active' => '']); ?>

<div class="workspace">
  <?php view('partials/wedding_nav', ['wedding' => $wedding, 'active' => 'details']); ?>

  <main class="ws-main">
    <div class="page-head">
      <div>
        <h1><?= e(t('ws.nav.details')) ?></h1>
        <span class="badge badge-<?= e($badge[$wedding['status']] ?? 'neutral') ?>"><?= e(t('wedding.status.' . $wedding['status'])) ?></span>
      </div>
    </div>

    <?php if ($wedding['status'] === 'draft'): ?>
      <div class="alert alert-info"><?= e(t('wedding.draft_hint')) ?></div>
    <?php endif; ?>

    <?php if ($wedding['cover_image']): ?>
      <img class="cover" src="<?= e(url('/uploads/covers/' . $wedding['cover_image'])) ?>" alt="<?= e($couple) ?>">
    <?php endif; ?>

    <div class="form-card">
      <dl class="detail-list">
        <dt><?= e(t('wedding.field.bride')) ?></dt>
        <dd><?= e($wedding['bride_name']) ?></dd>

        <dt><?= e(t('wedding.field.groom')) ?></dt>
        <dd><?= e($wedding['groom_name']) ?></dd>

        <dt><?= e(t('wedding.field.date')) ?></dt>
        <dd><?= e($wedding['wedding_date']) ?></dd>

        <dt><?= e(t('wedding.field.time')) ?></dt>
        <dd><?= e($wedding['wedding_time'] ? substr($wedding['wedding_time'], 0, 5) : '—') ?></dd>

        <dt><?= e(t('wedding.field.venue')) ?></dt>
        <dd><?= e($wedding['venue']) ?></dd>

        <dt><?= e(t('wedding.field.description')) ?></dt>
        <dd><?= $wedding['description'] ? nl2br(e($wedding['description'])) : '—' ?></dd>
      </dl>

      <div class="actions-row">
        <a class="btn-outline" href="<?= e(url('/weddings/' . $wedding['id'] . '/edit')) ?>"><?= e(t('wedding.btn.edit')) ?></a>

        <?php if ($wedding['status'] === 'draft'): ?>
          <form method="post" action="<?= e(url('/weddings/' . $wedding['id'] . '/activate')) ?>">
            <?= csrf_field() ?>
            <button class="btn-link" type="submit"><?= e(t('wedding.btn.activate')) ?></button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>
</body>
</html>