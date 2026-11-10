<?php
use App\Helpers\Url;

$base = '/weddings/' . $wedding['id'] . '/checkin';
$scanPath = '/scan/' . $wedding['slug'];
$cards = [
    ['checkin.stat.guests', $counts['guests']],
    ['checkin.stat.people', $counts['people']],
    ['checkin.stat.expected', $stats['accepted_seats']],
];
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(t('checkin.meta_title')) ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/owner.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/workspace.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/guests.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/checkin.css')) ?>">
  <script src="<?= e(asset('js/copy.js')) ?>" defer></script>
</head>
<body>
<?php view('partials/owner_header', ['active' => '']); ?>

<div class="workspace">
  <?php view('partials/wedding_nav', ['wedding' => $wedding, 'active' => 'checkin']); ?>

  <main class="ws-main">
    <div class="page-head">
      <div>
        <h1><?= e(t('checkin.title')) ?></h1>
        <p class="summary-line"><?= e(t('checkin.intro')) ?></p>
      </div>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-success"><?= e(t($flash)) ?></div>
    <?php endif; ?>

    <?php if ($wedding['status'] !== 'active'): ?>
      <div class="alert alert-info">
        <?= e(t('checkin.warn.not_active')) ?>
        <a href="<?= e(url('/weddings/' . $wedding['id'] . '/details')) ?>"><?= e(t('inv.draft_link')) ?></a>
      </div>
    <?php endif; ?>

    <div class="stat-grid">
      <?php foreach ($cards as [$label, $value]): ?>
        <div class="stat-card">
          <div class="num"><?= e(number_format($value)) ?></div>
          <div class="label"><?= e(t($label)) ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <h2 class="section-title"><?= e(t('checkin.scanner.title')) ?></h2>
    <div class="form-card wide">
      <p class="muted"><?= e(t('checkin.scanner.text')) ?></p>

      <label><?= e(t('checkin.scanner.link')) ?></label>
      <div class="link-box"><?= e(Url::absolute($scanPath)) ?></div>
      <div class="panel-row">
        <button type="button" class="btn-outline"
                data-copy-path="<?= e(url($scanPath)) ?>"
                data-copied="<?= e(t('inv.btn.copied')) ?>"><?= e(t('checkin.scanner.copy')) ?></button>
      </div>

      <h3 class="sub-title"><?= e(t('checkin.pin.title')) ?></h3>

      <?php if ($newPin !== null): ?>
        <div class="pin-reveal">
          <p><strong><?= e(t('checkin.pin.new_title')) ?></strong></p>
          <div class="pin-code"><?= e($newPin) ?></div>
          <p class="hint"><?= e(t('checkin.pin.new_note')) ?></p>
        </div>
      <?php endif; ?>

      <p class="muted"><?= e(t($hasPin ? 'checkin.pin.set' : 'checkin.pin.none')) ?></p>

      <div class="panel-row">
        <form method="post" action="<?= e(url($base . '/pin')) ?>"
              <?php if ($hasPin): ?>onsubmit="return confirm(<?= e(json_encode(t('checkin.btn.confirm_generate'), JSON_UNESCAPED_UNICODE)) ?>)"<?php endif; ?>>
          <?= csrf_field() ?>
          <button class="btn-link" type="submit"><?= e(t($hasPin ? 'checkin.btn.regenerate' : 'checkin.btn.generate')) ?></button>
        </form>

        <?php if ($hasPin): ?>
          <form method="post" action="<?= e(url($base . '/pin/disable')) ?>"
                onsubmit="return confirm(<?= e(json_encode(t('checkin.btn.confirm_disable'), JSON_UNESCAPED_UNICODE)) ?>)">
            <?= csrf_field() ?>
            <button class="btn-small btn-danger" type="submit"><?= e(t('checkin.btn.disable')) ?></button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <h2 class="section-title"><?= e(t('checkin.recent.title')) ?></h2>
    <?php if (!$recent): ?>
      <div class="empty"><?= e(t('checkin.recent.empty')) ?></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th><?= e(t('checkin.col.guest')) ?></th>
              <th><?= e(t('checkin.col.people')) ?></th>
              <th><?= e(t('checkin.col.time')) ?></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent as $r): ?>
              <tr>
                <td data-label="<?= e(t('checkin.col.guest')) ?>"><?= e($r['full_name']) ?></td>
                <td data-label="<?= e(t('checkin.col.people')) ?>"><?= e((string) $r['seats']) ?></td>
                <td data-label="<?= e(t('checkin.col.time')) ?>"><?= e(substr($r['checked_in_at'], 0, 16)) ?></td>
                <td class="row-action">
                  <form method="post" action="<?= e(url($base . '/' . $r['guest_id'] . '/undo')) ?>"
                        onsubmit="return confirm(<?= e(json_encode(t('checkin.btn.confirm_undo'), JSON_UNESCAPED_UNICODE)) ?>)">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-small btn-neutral"><?= e(t('checkin.btn.undo')) ?></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </main>
</div>
</body>
</html>