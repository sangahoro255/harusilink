<?php
use App\Services\InvitationTemplates;

$couple = $row['bride_name'] . ' & ' . $row['groom_name'];
$state = $row['rsvp_status'];
$bad = in_array($flash, ['rsvp.flash.limit', 'rsvp.flash.error', 'rsvp.flash.closed'], true);
$action = url('/i/' . $token . '/rsvp');
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($couple) ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/invitation.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/rsvp.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/qr.css')) ?>">
</head>
<body class="inv-page">
  <div class="inv-lang">
    <?php view('partials/lang_switch'); ?>
  </div>

  <?php InvitationTemplates::render($row, $settings, $row['full_name'], (int) $row['seats']); ?>

  <?php if ($canRsvp || $flash): ?>
    <section class="rsvp-box" id="rsvp">
      <?php if ($flash): ?>
        <div class="alert <?= $bad ? 'alert-danger' : 'alert-success' ?>"><?= e(t($flash)) ?></div>
      <?php endif; ?>

      <?php if ($canRsvp && $state === 'pending'): ?>
        <h3><?= e(t('rsvp.card.title')) ?></h3>
        <p><?= e(t('rsvp.card.subtitle')) ?></p>
        <form method="post" action="<?= e($action) ?>" class="rsvp-actions">
          <?= csrf_field() ?>
          <button class="btn" type="submit" name="status" value="accepted"><?= e(t('rsvp.card.accept')) ?></button>
          <button class="btn-ghost" type="submit" name="status" value="declined"><?= e(t('rsvp.card.decline')) ?></button>
        </form>
      <?php elseif ($canRsvp && $state === 'accepted'): ?>
        <span class="rsvp-state accepted">✓ <?= e(t('rsvp.card.state.accepted')) ?></span>
        <p><?= e(t('rsvp.card.thanks_accepted')) ?></p>
        <form method="post" action="<?= e($action) ?>" class="rsvp-actions">
          <?= csrf_field() ?>
          <button class="btn-ghost" type="submit" name="status" value="declined"><?= e(t('rsvp.card.change_decline')) ?></button>
        </form>
      <?php elseif ($canRsvp && $state === 'declined'): ?>
        <span class="rsvp-state declined">✕ <?= e(t('rsvp.card.state.declined')) ?></span>
        <p><?= e(t('rsvp.card.thanks_declined')) ?></p>
        <form method="post" action="<?= e($action) ?>" class="rsvp-actions">
          <?= csrf_field() ?>
          <button class="btn-ghost" type="submit" name="status" value="accepted"><?= e(t('rsvp.card.change_accept')) ?></button>
        </form>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <?php if ($qr !== null): ?>
    <section class="qr-box" id="entry-pass">
      <h3><?= e(t('qr.card.title')) ?></h3>
      <p><?= e(t('qr.card.text')) ?></p>
      <img class="qr-img" src="<?= e($qr) ?>" alt="<?= e(t('qr.card.alt')) ?>" width="240" height="240">
      <p class="qr-seats"><?= e(t('qr.card.seats', ['seats' => (int) $row['seats']])) ?></p>
      <p class="qr-tip"><?= e(t('qr.card.tip')) ?></p>
      <a class="qr-download" href="<?= e(url('/i/' . $token . '/qr')) ?>"><?= e(t('qr.card.download')) ?></a>
    </section>
  <?php endif; ?>

  <footer class="inv-foot">
    <span><?= e(t('inv.card.powered')) ?></span>
    <div class="logo-box">
      <img src="<?= e(asset('logos/harusilink-logo.png')) ?>" alt="HarusiLink">
    </div>
  </footer>
</body>
</html>