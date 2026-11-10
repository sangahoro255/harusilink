<?php
$base = '/weddings/' . $wedding['id'] . '/guests';
$pageUrl = function (int $p) use ($base, $q, $rsvp, $cat): string {
    $query = http_build_query(array_filter([
        'q'    => $q,
        'rsvp' => $rsvp,
        'cat'  => $cat > 0 ? $cat : null,
        'page' => $p > 1 ? $p : null,
    ]));
    return url($base) . ($query !== '' ? '?' . $query : '');
};
$rsvpBadge = ['pending' => 'neutral', 'accepted' => 'active', 'declined' => 'suspended'];
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(t('guest.list.meta_title')) ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/owner.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/workspace.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/guests.css')) ?>">
</head>
<body>
<?php view('partials/owner_header', ['active' => '']); ?>

<div class="workspace">
  <?php view('partials/wedding_nav', ['wedding' => $wedding, 'active' => 'guests']); ?>

  <main class="ws-main">
    <div class="page-head">
      <div>
        <h1><?= e(t('guest.list.title')) ?></h1>
        <p class="summary-line"><?= e(t('guest.list.summary', ['guests' => $summary['guests'], 'seats' => $summary['seats']])) ?></p>
      </div>
      <div class="head-actions">
        <a class="btn-outline" href="<?= e(url($base . '/import')) ?>"><?= e(t('import.btn')) ?></a>
        <a class="btn-link" href="<?= e(url($base . '/create')) ?>"><?= e(t('guest.list.add')) ?></a>
      </div>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-success"><?= e(t($flash)) ?></div>
    <?php endif; ?>

    <?php if ($summary['guests'] === 0): ?>
      <div class="empty">
        <strong><?= e(t('guest.list.empty_title')) ?></strong><br>
        <?= e(t('guest.list.empty_text')) ?><br>
        <a href="<?= e(url($base . '/import')) ?>"><?= e(t('import.empty_link')) ?></a>
      </div>
    <?php else: ?>
      <form method="get" action="<?= e(url($base)) ?>" class="filters">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('guest.search_placeholder')) ?>">
        <select name="rsvp">
          <option value=""><?= e(t('guest.filter.all_rsvp')) ?></option>
          <?php foreach (['pending', 'accepted', 'declined'] as $s): ?>
            <option value="<?= e($s) ?>" <?= $rsvp === $s ? 'selected' : '' ?>><?= e(t('guest.rsvp.' . $s)) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="cat">
          <option value=""><?= e(t('guest.filter.all_categories')) ?></option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= e((string) $c['id']) ?>" <?= $cat === (int) $c['id'] ? 'selected' : '' ?>>
              <?= e($c['name'] ?: t('cat.' . $c['code'])) ?>
            </option>
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
                <th><?= e(t('guest.col.category')) ?></th>
                <th><?= e(t('guest.col.seats')) ?></th>
                <th><?= e(t('guest.col.rsvp')) ?></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $g): ?>
                <tr>
                  <td data-label="<?= e(t('guest.col.name')) ?>">
                    <?= e($g['full_name']) ?>
                    <?php if ($g['email']): ?><span class="sub"><?= e($g['email']) ?></span><?php endif; ?>
                  </td>
                  <td data-label="<?= e(t('guest.col.phone')) ?>"><?= e($g['phone'] ?: '—') ?></td>
                  <td data-label="<?= e(t('guest.col.category')) ?>"><?= e($g['category_code'] ? ($g['category_name'] ?: t('cat.' . $g['category_code'])) : '—') ?></td>
                  <td data-label="<?= e(t('guest.col.seats')) ?>"><?= e((string) $g['seats']) ?></td>
                  <td data-label="<?= e(t('guest.col.rsvp')) ?>">
                    <span class="badge badge-<?= e($rsvpBadge[$g['rsvp_status']] ?? 'neutral') ?>"><?= e(t('guest.rsvp.' . $g['rsvp_status'])) ?></span>
                  </td>
                  <td class="row-action">
                    <div class="row-actions">
                      <a class="btn-outline" href="<?= e(url($base . '/' . $g['id'] . '/edit')) ?>"><?= e(t('guest.btn.edit')) ?></a>
                      <form method="post" action="<?= e(url($base . '/' . $g['id'] . '/delete')) ?>"
                            onsubmit="return confirm(<?= e(json_encode(t('guest.btn.confirm_delete'), JSON_UNESCAPED_UNICODE)) ?>)">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-small btn-danger"><?= e(t('guest.btn.delete')) ?></button>
                      </form>
                    </div>
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