<?php
$badge = ['draft' => 'neutral', 'active' => 'active', 'completed' => 'done', 'archived' => 'neutral'];
$couple = $wedding['bride_name'] . ' & ' . $wedding['groom_name'];

if ($days > 1) {
    $countdown = t('ws.countdown.days', ['days' => $days]);
} elseif ($days === 1) {
    $countdown = t('ws.countdown.tomorrow');
} elseif ($days === 0) {
    $countdown = t('ws.countdown.today');
} else {
    $countdown = t('ws.countdown.past');
}

$doneCount = count(array_filter($checklist, fn ($item) => $item['done']));

$cards = [
    ['guests',        'ws.stat.guests',        number_format($stats['guests'])],
    ['accepted',      'ws.stat.accepted',      number_format($stats['accepted'])],
    ['declined',      'ws.stat.declined',      number_format($stats['declined'])],
    ['pending',       'ws.stat.pending',       number_format($stats['pending'])],
    ['checked_in',    'ws.stat.checked_in',    number_format($stats['checked_in'])],
    ['contributions', 'ws.stat.contributions', 'TZS ' . number_format($stats['contributions'])],
];
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($couple) ?> – HarusiLink</title>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/owner.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/workspace.css')) ?>">
</head>
<body>
<?php view('partials/owner_header', ['active' => '']); ?>

<div class="workspace">
  <?php view('partials/wedding_nav', ['wedding' => $wedding, 'active' => 'dashboard']); ?>

  <main class="ws-main">
    <?php if ($flash): ?>
      <div class="alert alert-success"><?= e(t($flash)) ?></div>
    <?php endif; ?>

    <section class="ws-hero">
      <?php if ($wedding['cover_image']): ?>
        <img class="cover" src="<?= e(url('/uploads/covers/' . $wedding['cover_image'])) ?>" alt="<?= e($couple) ?>">
      <?php endif; ?>
      <div class="hero-top">
        <h1><?= e($couple) ?></h1>
        <span class="badge badge-<?= e($badge[$wedding['status']] ?? 'neutral') ?>"><?= e(t('wedding.status.' . $wedding['status'])) ?></span>
      </div>
      <p class="muted">
        <?= e($wedding['wedding_date']) ?><?= $wedding['wedding_time'] ? ' · ' . e(substr($wedding['wedding_time'], 0, 5)) : '' ?>
        · <?= e($wedding['venue']) ?>
      </p>
      <span class="countdown"><?= e($countdown) ?></span>
    </section>

    <div class="stat-grid">
      <?php foreach ($cards as [$key, $label, $value]): ?>
        <div class="stat-card">
          <div class="num"><?= e($value) ?></div>
          <div class="label"><?= e(t($label)) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="hint"><?= e(t('ws.stats_hint')) ?></p>

    <section class="form-card checklist-card">
      <h2><?= e(t('ws.checklist.title')) ?> <span class="muted">(<?= e((string) $doneCount) ?>/<?= e((string) count($checklist)) ?>)</span></h2>
      <ul class="checklist">
        <?php foreach ($checklist as $item): ?>
          <li class="<?= $item['done'] ? 'done' : '' ?>">
            <span class="mark"><?= $item['done'] ? '✓' : '' ?></span>
            <span class="text"><?= e(t($item['label'])) ?></span>
            <?php if (!$item['done']): ?>
              <?php if ($item['link'] !== null): ?>
                <a class="btn-outline" href="<?= e(url($item['link'])) ?>"><?= e(t('ws.check.go')) ?></a>
              <?php else: ?>
                <span class="tag"><?= e(t('ws.soon')) ?></span>
              <?php endif; ?>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  </main>
</div>
</body>
</html>