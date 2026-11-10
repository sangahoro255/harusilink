<?php
$base = '/weddings/' . $wedding['id'] . '/rsvp';
$pageUrl = function (int $p) use ($base, $q, $filter): string {
    $query = http_build_query(array_filter([
        'q'      => $q,
        'status' => $filter,
        'page'   => $p > 1 ? $p : null,
    ]));
    return url($base) . ($query !== '' ? '?' . $query : '');
};
$badge = ['pending' => 'neutral', 'accepted' => 'active', 'declined' => 'suspended'];
$cards = [
    ['ws.stat.guests', $stats['guests']],
    ['ws.stat.accepted', $stats['accepted']],
    ['ws.stat.declined', $stats['declined']],
    ['ws.stat.pending', $stats['pending']],
    ['rsvp.stat.accepted_seats', $stats['accepted_seats']],
];
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(t('rsvp.meta_title')) ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/owner.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/workspace.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/guests.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/rsvp.css')) ?>">
</head>
<body>
<?php view('partials/owner_header', ['active' => '']); ?>

<div class="workspace">
  <?php view('partials/wedding_nav', ['wedding' => $wedding, 'active' => 'rsvp']); ?>

  <main class="ws-main">
    <div class="page-head">
      <div>
        <h1><?= e(t('rsvp.title')) ?></h1>
        <p class="summary-line"><?= e(t('rsvp.intro')) ?></p>
      </div>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-success"><?= e(t($flash)) ?></div>
    <?php endif; ?>

    <div class="stat-grid">
      <?php foreach ($cards as [$label, $value]): ?>
        <div class="stat-card">
          <div class="num"><?= e(number_format($value)) ?></div>
          <div class="label"><?= e(t($label)) ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($stats['guests'] === 0): ?>
      <div class="empty spaced-top">
        <?= e(t('rsvp.empty')) ?><br>
        <a href="<?= e(url('/weddings/' . $wedding['id'] . '/guests/create')) ?>"><?= e(t('rsvp.empty_link')) ?></a>
      </div>
    <?php else: ?>
      <h2 class="section-title"><?= e(t('rsvp.recent.title')) ?></h2>
      <div class="form-card">
        <?php if (!$recent): ?>
          <p class="muted"><?= e(t('rsvp.recent.empty')) ?></p>
        <?php else: ?>
          <ul class="recent-list">
            <?php foreach ($recent as $r): ?>
              <li>
                <span>
                  <span class="who"><?= e($r['full_name']) ?></span>
                  <span class="badge badge-<?= e($badge[$r['status']] ?? 'neutral') ?>"><?= e(t('guest.rsvp.' . $r['status'])) ?></span>
                </span>
                <span class="when"><?= e(t('rsvp.source.' . $r['source'])) ?> · <?= e(substr($r['created_at'], 0, 16)) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <h2 class="section-title"><?= e(t('rsvp.list.title')) ?></h2>
      <form method="get" action="<?= e(url($base)) ?>" class="filters">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('guest.search_placeholder')) ?>">
        <select name="status">
          <option value=""><?= e(t('guest.filter.all_rsvp')) ?></option>
          <?php foreach (['pending', 'accepted', 'declined'] as $s): ?>
            <option value="<?= e($s) ?>" <?= $filter === $s ? 'selected' : '' ?>><?= e(t('guest.rsvp.' . $s)) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn" type="submit"><?= e(t('guest.filter.button')) ?></button>
      </form>

      <p class="muted"><?= e(t('guest.list.found', ['count' => $total])) ?></p>

      <?php if (!$rows): ?>
        <div class="empty"><?= e(t('guest.list.no_results')) ?></div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th><?= e(t('guest.col.name')) ?></th>
                <th><?= e(t('guest.col.phone')) ?></th>
                <th><?= e(t('guest.col.seats')) ?></th>
                <th><?= e(t('guest.col.rsvp')) ?></th>
                <th><?= e(t('rsvp.col.change')) ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $g): ?>
                <tr>
                  <td data-label="<?= e(t('guest.col.name')) ?>"><?= e($g['full_name']) ?></td>
                  <td data-label="<?= e(t('guest.col.phone')) ?>"><?= e($g['phone'] ?: '—') ?></td>
                  <td data-label="<?= e(t('guest.col.seats')) ?>"><?= e((string) $g['seats']) ?></td>
                  <td data-label="<?= e(t('guest.col.rsvp')) ?>">
                    <span class="badge badge-<?= e($badge[$g['rsvp_status']] ?? 'neutral') ?>"><?= e(t('guest.rsvp.' . $g['rsvp_status'])) ?></span>
                  </td>
                  <td class="row-action">
                    <form method="post" action="<?= e(url($base . '/' . $g['id'] . '/set')) ?>" class="inline-set">
                      <?= csrf_field() ?>
                      <input type="hidden" name="q" value="<?= e($q) ?>">
                      <input type="hidden" name="filter" value="<?= e($filter) ?>">
                      <input type="hidden" name="page" value="<?= e((string) $page) ?>">
                      <select name="status" aria-label="<?= e(t('rsvp.col.change')) ?>">
                        <?php foreach (['pending', 'accepted', 'declined'] as $s): ?>
                          <option value="<?= e($s) ?>" <?= $g['rsvp_status'] === $s ? 'selected' : '' ?>><?= e(t('guest.rsvp.' . $s)) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <button type="submit" class="btn-small btn-neutral"><?= e(t('rsvp.btn.save')) ?></button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php view('partials/pager', ['page' => $page, 'pages' => $pages, 'pageUrl' => $pageUrl]); ?>
      <?php endif; ?>
    <?php endif; ?>
  </main>
</div>
</body>
</html>