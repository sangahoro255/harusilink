<?php
$map = [
    'valid'    => ['res-valid',   '✓', 'verify.valid'],
    'admitted' => ['res-valid',   '✓', 'verify.admitted'],
    'confirm'  => ['res-confirm', '!', 'verify.confirm'],
    'already'  => ['res-bad',     '✕', 'verify.already'],
    'declined' => ['res-bad',     '✕', 'verify.declined'],
    'revoked'  => ['res-bad',     '✕', 'verify.revoked'],
    'closed'   => ['res-bad',     '✕', 'verify.closed'],
    'invalid'  => ['res-bad',     '✕', 'verify.invalid'],
];

$state = $result['state'];
[$class, $glyph, $prefix] = $map[$state] ?? $map['invalid'];
$time = !empty($result['checked_in_at']) ? substr($result['checked_in_at'], 0, 16) : '';
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e(t($prefix . '.title')) ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/checkin.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/scanner.css')) ?>">
</head>
<body>
<div class="scan-wrap">
  <div class="scan-card">
    <?php if (!empty($manual) && $state !== 'invalid'): ?>
      <p class="manual-note"><?= e(t('verify.manual_note')) ?></p>
    <?php endif; ?>

    <section class="res-panel <?= e($class) ?>" role="status" aria-live="polite">
      <div class="res-icon" aria-hidden="true"><?= e($glyph) ?></div>
      <h1 class="res-title"><?= e(t($prefix . '.title')) ?></h1>
      <p class="res-text"><?= e(t($prefix . '.text', ['time' => $time])) ?></p>

      <?php if ($state !== 'invalid'): ?>
        <dl class="res-info">
          <div><dt><?= e(t('verify.guest')) ?></dt><dd><?= e($result['name']) ?></dd></div>
          <div><dt><?= e(t('verify.people')) ?></dt><dd><?= e((string) $result['seats']) ?></dd></div>
          <div><dt><?= e(t('verify.rsvp')) ?></dt><dd><?= e(t('guest.rsvp.' . $result['rsvp'])) ?></dd></div>
          <div><dt><?= e(t('verify.couple')) ?></dt><dd><?= e($result['couple']) ?></dd></div>
        </dl>
      <?php endif; ?>

      <?php if ($state === 'valid' || $state === 'confirm'): ?>
        <form method="post" action="<?= e($admitUrl) ?>">
          <?= csrf_field() ?>
          <?php if ($state === 'confirm'): ?>
            <input type="hidden" name="override" value="1">
          <?php endif; ?>
          <button class="big-btn <?= $state === 'confirm' ? 'amber' : 'green' ?>" type="submit">
            <?= e(t($state === 'confirm' ? 'verify.btn.override' : 'verify.btn.admit')) ?>
          </button>
        </form>
      <?php endif; ?>
    </section>

    <a class="res-back btn-outline" href="<?= e(url('/scan/' . $wedding['slug'])) ?>"><?= e(t('verify.back')) ?></a>
  </div>
</div>
</body>
</html>