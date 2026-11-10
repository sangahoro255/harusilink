<?php
use App\Services\InvitationTemplates;

$base = '/weddings/' . $wedding['id'] . '/invitation';
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(t('inv.design.meta_title')) ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/owner.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/workspace.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/invitation.css')) ?>">
</head>
<body>
<?php view('partials/owner_header', ['active' => '']); ?>

<div class="workspace">
  <?php view('partials/wedding_nav', ['wedding' => $wedding, 'active' => 'invitation']); ?>

  <main class="ws-main">
    <div class="page-head">
      <h1><?= e(t('inv.design.title')) ?></h1>
      <a class="btn-outline" href="<?= e(url($base)) ?>"><?= e(t('inv.design.back')) ?></a>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-success"><?= e(t($flash)) ?></div>
    <?php endif; ?>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-danger"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" action="<?= e(url($base . '/design')) ?>" class="form-card" novalidate>
      <?= csrf_field() ?>

      <h2><?= e(t('inv.design.template')) ?></h2>
      <div class="opt-grid">
        <?php foreach (InvitationTemplates::TEMPLATES as $code): ?>
          <label class="opt">
            <input type="radio" name="template" value="<?= e($code) ?>" <?= $old['template'] === $code ? 'checked' : '' ?>>
            <span>
              <strong><?= e(t('inv.tpl.' . $code)) ?></strong>
              <small><?= e(t('inv.tpl.' . $code . '_desc')) ?></small>
            </span>
          </label>
        <?php endforeach; ?>
      </div>

      <h2 class="spaced"><?= e(t('inv.design.palette')) ?></h2>
      <div class="opt-grid">
        <?php foreach (InvitationTemplates::PALETTES as $code => [$primary, $accent]): ?>
          <label class="opt">
            <input type="radio" name="palette" value="<?= e($code) ?>" <?= $old['palette'] === $code ? 'checked' : '' ?>>
            <span class="swatches">
              <span class="swatch" style="background:<?= e($primary) ?>"></span>
              <span class="swatch" style="background:<?= e($accent) ?>"></span>
            </span>
            <span><strong><?= e(t('inv.palette.' . $code)) ?></strong></span>
          </label>
        <?php endforeach; ?>

        <label class="opt">
          <input type="radio" name="palette" value="custom" <?= $old['palette'] === 'custom' ? 'checked' : '' ?>>
          <span><strong><?= e(t('inv.palette.custom')) ?></strong></span>
        </label>
      </div>

      <div class="color-row">
        <div>
          <label for="primary_color"><?= e(t('inv.color.primary')) ?></label>
          <input id="primary_color" name="primary_color" type="color" value="<?= e($old['primary_color']) ?>">
        </div>
        <div>
          <label for="accent_color"><?= e(t('inv.color.accent')) ?></label>
          <input id="accent_color" name="accent_color" type="color" value="<?= e($old['accent_color']) ?>">
        </div>
      </div>
      <p class="hint"><?= e(t('inv.color.hint')) ?></p>

      <h2 class="spaced"><?= e(t('inv.design.message')) ?></h2>
      <textarea id="message" name="message" rows="5" maxlength="600" placeholder="<?= e(t('inv.default_message')) ?>"><?= e($old['message']) ?></textarea>
      <p class="hint"><?= e(t('inv.design.message_hint')) ?></p>

      <button class="btn" type="submit"><?= e(t('inv.design.save')) ?></button>
    </form>

    <h2 class="section-title"><?= e(t('inv.design.preview')) ?></h2>
    <p class="muted"><?= e(t('inv.preview.note')) ?></p>
    <div class="preview-frame">
      <?php InvitationTemplates::render($wedding, $settings, t('inv.sample_guest'), 2); ?>
    </div>
  </main>
</div>
</body>
</html>