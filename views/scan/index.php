<?php
$couple = $authed ? $wedding['bride_name'] . ' & ' . $wedding['groom_name'] : '';
$rsvpBadge = ['pending' => 'neutral', 'accepted' => 'active', 'declined' => 'suspended'];
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e(t('scan.meta_title')) ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/checkin.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/scanner.css')) ?>">
  <?php if ($authed): ?>
    <?php if (is_file(BASE_PATH . '/public/assets/js/jsQR.js')): ?>
      <script src="<?= e(asset('js/jsQR.js')) ?>" defer></script>
    <?php endif; ?>
    <script src="<?= e(asset('js/scanner.js')) ?>" defer></script>
  <?php endif; ?>
</head>
<body>
<div class="scan-wrap">
  <div class="scan-card">
    <?php view('partials/lang_switch'); ?>
    <div class="logo-box">
      <img src="<?= e(asset('logos/harusilink-logo.png')) ?>" alt="HarusiLink">
    </div>
    <h1><?= e(t('scan.title')) ?></h1>

    <?php if (!$authed): ?>
      <p class="sub"><?= e(t('scan.pin.help')) ?></p>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e(t($error)) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= e(url('/scan/' . $slug)) ?>" novalidate>
        <?= csrf_field() ?>
        <label for="pin"><?= e(t('scan.pin.label')) ?></label>
        <input id="pin" name="pin" type="password" inputmode="numeric" pattern="[0-9]*" maxlength="6"
               autocomplete="off" class="pin-input" required>
        <button class="btn" type="submit"><?= e(t('scan.pin.button')) ?></button>
      </form>
    <?php else: ?>
      <p class="sub"><?= e($couple) ?></p>

      <?php if ($wedding['status'] !== 'active'): ?>
        <div class="alert alert-danger"><?= e(t('scan.not_active')) ?></div>
      <?php endif; ?>

      <?php if ($flash): ?>
        <div class="alert alert-danger"><?= e(t($flash)) ?></div>
      <?php endif; ?>

      <div class="scan-count"><?= e(t('scan.home.counts', ['guests' => $counts['guests'], 'people' => $counts['people']])) ?></div>

      <section class="scan-section cam" id="cam"
               data-verify-base="<?= e(url('/verify/')) ?>"
               data-msg-idle="<?= e(t('scan.cam.hint')) ?>"
               data-msg-scanning="<?= e(t('scan.cam.scanning')) ?>"
               data-msg-found="<?= e(t('scan.cam.found')) ?>"
               data-msg-denied="<?= e(t('scan.cam.denied')) ?>"
               data-msg-insecure="<?= e(t('scan.cam.insecure')) ?>"
               data-msg-nolib="<?= e(t('scan.cam.nolib')) ?>"
               data-msg-notours="<?= e(t('scan.cam.notours')) ?>">
        <h2><?= e(t('scan.cam.title')) ?></h2>
        <div class="cam-view">
          <video id="cam-video" muted playsinline></video>
          <div class="cam-frame"></div>
        </div>
        <p id="cam-status" class="cam-status" role="status" aria-live="polite"><?= e(t('scan.cam.hint')) ?></p>
        <button type="button" id="cam-start" class="btn"><?= e(t('scan.cam.start')) ?></button>
        <button type="button" id="cam-stop" class="btn-ghost"><?= e(t('scan.cam.stop')) ?></button>
        <p class="scan-how"><?= e(t('scan.cam.alt')) ?></p>
      </section>

      <section class="scan-section">
        <h2><?= e(t('scan.search.title')) ?></h2>
        <form method="post" action="<?= e(url('/scan/' . $slug . '/search')) ?>" novalidate>
          <?= csrf_field() ?>
          <label for="q"><?= e(t('scan.search.label')) ?></label>
          <input id="q" name="q" type="search" value="<?= e($query) ?>" maxlength="60" minlength="2"
                 autocomplete="off" placeholder="<?= e(t('scan.search.placeholder')) ?>" required>
          <button class="btn" type="submit"><?= e(t('scan.search.button')) ?></button>
        </form>

        <?php if ($results !== null): ?>
          <?php if (!$results): ?>
            <p class="scan-how spaced"><?= e(t('scan.search.none')) ?></p>
          <?php else: ?>
            <ul class="search-results">
              <?php foreach ($results as $g): ?>
                <li class="sr-row">
                  <div>
                    <div class="sr-name"><?= e($g['full_name']) ?></div>
                    <div class="sr-meta">
                      <?= e(t('scan.search.seats', ['seats' => $g['seats']])) ?>
                      · <?= e(t('guest.rsvp.' . $g['rsvp_status'])) ?>
                      <?php if ($g['phone_hint'] !== ''): ?> · <?= e($g['phone_hint']) ?><?php endif; ?>
                    </div>
                    <?php if ($g['checked_in_at'] !== null): ?>
                      <span class="tag-done"><?= e(t('scan.search.checked')) ?></span>
                    <?php endif; ?>
                  </div>
                  <a class="sr-open" href="<?= e(url('/scan/' . $slug . '/guest/' . $g['id'])) ?>"><?= e(t('scan.search.open')) ?></a>
                </li>
              <?php endforeach; ?>
            </ul>
            <?php if (count($results) >= 10): ?>
              <p class="scan-how spaced"><?= e(t('scan.search.cap')) ?></p>
            <?php endif; ?>
          <?php endif; ?>
        <?php endif; ?>
      </section>

      <section class="scan-section">
        <h2><?= e(t('scan.manual.title')) ?></h2>
        <form method="post" action="<?= e(url('/scan/' . $slug . '/lookup')) ?>" novalidate>
          <?= csrf_field() ?>
          <label for="code"><?= e(t('scan.manual.label')) ?></label>
          <input id="code" name="code" type="text" autocomplete="off" autocapitalize="off" spellcheck="false" required>
          <button class="btn" type="submit"><?= e(t('scan.manual.button')) ?></button>
        </form>
      </section>

      <section class="scan-section">
        <h2><?= e(t('scan.recent.title')) ?></h2>
        <?php if (!$recent): ?>
          <p class="scan-how"><?= e(t('scan.recent.empty')) ?></p>
        <?php else: ?>
          <ul class="recent-mini">
            <?php foreach ($recent as $r): ?>
              <li>
                <span><?= e($r['full_name']) ?> (<?= e((string) $r['seats']) ?>)</span>
                <span class="when"><?= e(substr($r['checked_in_at'], 11, 5)) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>

      <form method="post" action="<?= e(url('/scan/' . $slug . '/logout')) ?>" class="scan-section">
        <?= csrf_field() ?>
        <button class="btn-ghost" type="submit"><?= e(t('scan.logout')) ?></button>
      </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>