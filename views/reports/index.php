<?php
use App\Helpers\Money;

$base = '/weddings/' . $wedding['id'] . '/reports';
$guests = max(1, $stats['guests']);
$answered = $stats['accepted'] + $stats['declined'];
$percent = fn (int $part, int $whole): int => $whole > 0 ? (int) round($part / $whole * 100) : 0;
$cards = [
    ['ws.stat.guests', number_format($stats['guests'])],
    ['ws.stat.accepted', number_format($stats['accepted'])],
    ['ws.stat.declined', number_format($stats['declined'])],
    ['ws.stat.pending', number_format($stats['pending'])],
    ['rsvp.stat.accepted_seats', number_format($stats['accepted_seats'])],
    ['checkin.stat.guests', number_format($checkins['guests'])],
    ['checkin.stat.people', number_format($checkins['people'])],
];
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <?php view('partials/ws_head', ['title' => t('rep.meta_title')]); ?>
</head>
<body>
<?php view('partials/owner_header', ['active' => '']); ?>

<div class="workspace">
  <?php view('partials/wedding_nav', ['wedding' => $wedding, 'active' => 'reports']); ?>

  <main class="ws-main">
    <div class="page-head">
      <div>
        <h1><?= e(t('rep.title')) ?></h1>
        <p class="summary-line"><?= e(t('rep.intro')) ?></p>
      </div>
    </div>

    <h2 class="section-title"><?= e(t('rep.guests.title')) ?></h2>
    <div class="stat-grid">
      <?php foreach ($cards as [$label, $value]): ?>
        <div class="stat-card">
          <div class="num"><?= e($value) ?></div>
          <div class="label"><?= e(t($label)) ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($stats['guests'] > 0): ?>
      <p class="hint"><?= e(t('rep.response_rate', ['percent' => $percent($answered, $guests)])) ?></p>
      <div class="progress"><span style="width:<?= e((string) $percent($answered, $guests)) ?>%"></span></div>

      <h2 class="section-title"><?= e(t('rep.categories.title')) ?></h2>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th><?= e(t('guest.col.category')) ?></th>
              <th><?= e(t('ws.stat.guests')) ?></th>
              <th><?= e(t('guest.col.seats')) ?></th>
              <th><?= e(t('ws.stat.accepted')) ?></th>
              <th><?= e(t('ws.stat.declined')) ?></th>
              <th><?= e(t('ws.stat.pending')) ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($categories as $c): ?>
              <tr>
                <td data-label="<?= e(t('guest.col.category')) ?>">
                  <?= e($c['code'] === 'none' ? t('rep.no_category') : ($c['name'] ?: t('cat.' . $c['code']))) ?>
                </td>
                <td data-label="<?= e(t('ws.stat.guests')) ?>"><?= e((string) $c['guests']) ?></td>
                <td data-label="<?= e(t('guest.col.seats')) ?>"><?= e((string) $c['seats']) ?></td>
                <td data-label="<?= e(t('ws.stat.accepted')) ?>"><?= e((string) $c['accepted']) ?></td>
                <td data-label="<?= e(t('ws.stat.declined')) ?>"><?= e((string) $c['declined']) ?></td>
                <td data-label="<?= e(t('ws.stat.pending')) ?>"><?= e((string) $c['pending']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <h2 class="section-title"><?= e(t('rep.money.title')) ?></h2>
    <div class="stat-grid">
      <div class="stat-card">
        <div class="num"><?= e($target !== null ? Money::format($target) : '—') ?></div>
        <div class="label"><?= e(t('contrib.stat.target')) ?></div>
      </div>
      <div class="stat-card">
        <div class="num"><?= e(Money::format($totals['paid'])) ?></div>
        <div class="label"><?= e(t('contrib.stat.raised', ['count' => $totals['paid_count']])) ?></div>
      </div>
      <div class="stat-card">
        <div class="num"><?= e(Money::format($totals['pledged'])) ?></div>
        <div class="label"><?= e(t('contrib.stat.pledged', ['count' => $totals['pledged_count']])) ?></div>
      </div>
    </div>

    <?php if ($methods): ?>
      <div class="table-wrap spaced-card">
        <table class="table">
          <thead>
            <tr>
              <th><?= e(t('contrib.col.method')) ?></th>
              <th><?= e(t('rep.col.count')) ?></th>
              <th><?= e(t('contrib.col.amount')) ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($methods as $m): ?>
              <tr>
                <td data-label="<?= e(t('contrib.col.method')) ?>"><?= e(t('contrib.method.' . $m['method'])) ?></td>
                <td data-label="<?= e(t('rep.col.count')) ?>"><?= e((string) $m['n']) ?></td>
                <td data-label="<?= e(t('contrib.col.amount')) ?>"><?= e(Money::format($m['total'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <h2 class="section-title"><?= e(t('rep.export.title')) ?></h2>
    <div class="form-card wide">
      <p class="muted"><?= e(t('rep.export.text')) ?></p>
      <div class="panel-row">
        <a class="btn-outline" href="<?= e(url($base . '/export/guests')) ?>"><?= e(t('rep.export.guests')) ?></a>
        <a class="btn-outline" href="<?= e(url($base . '/export/contributions')) ?>"><?= e(t('rep.export.contributions')) ?></a>
        <a class="btn-outline" href="<?= e(url($base . '/export/checkins')) ?>"><?= e(t('rep.export.checkins')) ?></a>
      </div>
    </div>
  </main>
</div>
</body>
</html>