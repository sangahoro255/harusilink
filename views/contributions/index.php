<?php
use App\Helpers\Format;
use App\Helpers\Money;

$base = '/weddings/' . $wedding['id'] . '/contributions';
$pageUrl = function (int $p) use ($base, $q, $status): string {
    $query = http_build_query(array_filter(['q' => $q, 'status' => $status, 'page' => $p > 1 ? $p : null]));
    return url($base) . ($query !== '' ? '?' . $query : '');
};
$badge = ['paid' => 'active', 'pledged' => 'done', 'cancelled' => 'suspended'];
$remaining = $target !== null ? max(0, $target - $totals['paid']) : null;
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <?php view('partials/ws_head', ['title' => t('contrib.meta_title')]); ?>
</head>
<body>
<?php view('partials/owner_header', ['active' => '']); ?>

<div class="workspace">
  <?php view('partials/wedding_nav', ['wedding' => $wedding, 'active' => 'contributions']); ?>

  <main class="ws-main">
    <div class="page-head">
      <h1><?= e(t('contrib.title')) ?></h1>
      <div class="head-actions">
        <a class="btn-outline" href="<?= e(url($base . '/settings')) ?>"><?= e(t('contrib.btn.settings')) ?></a>
        <a class="btn-link" href="<?= e(url($base . '/create')) ?>"><?= e(t('contrib.btn.add')) ?></a>
      </div>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-success"><?= e(t($flash)) ?></div>
    <?php endif; ?>

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
      <?php if ($remaining !== null): ?>
        <div class="stat-card">
          <div class="num"><?= e(Money::format($remaining)) ?></div>
          <div class="label"><?= e(t('contrib.stat.remaining')) ?></div>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($percent !== null): ?>
      <div class="progress" role="progressbar" aria-valuenow="<?= e((string) $percent) ?>" aria-valuemin="0" aria-valuemax="100">
        <span style="width:<?= e((string) $percent) ?>%"></span>
      </div>
      <p class="hint"><?= e(t('contrib.progress', ['percent' => $percent])) ?></p>
    <?php else: ?>
      <p class="hint"><?= e(t('contrib.no_target')) ?></p>
    <?php endif; ?>

    <?php if ($settings['deadline']): ?>
      <p class="muted">
        <?= e(t('contrib.deadline', ['date' => Format::date($settings['deadline'])])) ?>
        <?php if ($daysLeft !== null): ?>
          · <?= e($daysLeft >= 0 ? t('contrib.days_left', ['days' => $daysLeft]) : t('contrib.deadline_passed')) ?>
        <?php endif; ?>
      </p>
    <?php endif; ?>

    <?php if ($settings['description'] || $settings['payment_info']): ?>
      <div class="form-card wide spaced-card">
        <?php if ($settings['description']): ?>
          <p><?= nl2br(e($settings['description'])) ?></p>
        <?php endif; ?>
        <?php if ($settings['payment_info']): ?>
          <h3 class="sub-title"><?= e(t('contrib.payment.title')) ?></h3>
          <p><?= nl2br(e($settings['payment_info'])) ?></p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <form method="get" action="<?= e(url($base)) ?>" class="filters">
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('contrib.search_placeholder')) ?>">
      <select name="status">
        <option value=""><?= e(t('contrib.filter.all_status')) ?></option>
        <?php foreach (['paid', 'pledged', 'cancelled'] as $s): ?>
          <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(t('contrib.status.' . $s)) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn" type="submit"><?= e(t('guest.filter.button')) ?></button>
    </form>

    <p class="muted"><?= e(t('guest.list.found', ['count' => $total])) ?></p>

    <?php if (!$rows): ?>
      <div class="empty"><?= e(t('contrib.empty')) ?></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th><?= e(t('contrib.col.name')) ?></th>
              <th><?= e(t('contrib.col.amount')) ?></th>
              <th><?= e(t('contrib.col.method')) ?></th>
              <th><?= e(t('contrib.col.status')) ?></th>
              <th><?= e(t('contrib.col.date')) ?></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td data-label="<?= e(t('contrib.col.name')) ?>">
                  <?= e($r['contributor_name']) ?>
                  <?php if ($r['phone']): ?><span class="sub"><?= e($r['phone']) ?></span><?php endif; ?>
                  <?php if ($r['reference']): ?><span class="sub"><?= e($r['reference']) ?></span><?php endif; ?>
                </td>
                <td data-label="<?= e(t('contrib.col.amount')) ?>"><strong><?= e(Money::format($r['amount'])) ?></strong></td>
                <td data-label="<?= e(t('contrib.col.method')) ?>"><?= e(t('contrib.method.' . $r['method'])) ?></td>
                <td data-label="<?= e(t('contrib.col.status')) ?>">
                  <span class="badge badge-<?= e($badge[$r['status']] ?? 'neutral') ?>"><?= e(t('contrib.status.' . $r['status'])) ?></span>
                </td>
                <td data-label="<?= e(t('contrib.col.date')) ?>"><?= e($r['contributed_on']) ?></td>
                <td class="row-action">
                  <div class="row-actions">
                    <?php if ($r['status'] === 'pledged'): ?>
                      <form method="post" action="<?= e(url($base . '/' . $r['id'] . '/paid')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-small btn-ok"><?= e(t('contrib.btn.paid')) ?></button>
                      </form>
                    <?php endif; ?>
                    <a class="btn-outline" href="<?= e(url($base . '/' . $r['id'] . '/edit')) ?>"><?= e(t('contrib.btn.edit')) ?></a>
                    <form method="post" action="<?= e(url($base . '/' . $r['id'] . '/delete')) ?>"
                          onsubmit="return confirm(<?= e(json_encode(t('contrib.btn.confirm_delete'), JSON_UNESCAPED_UNICODE)) ?>)">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn-small btn-danger"><?= e(t('contrib.btn.delete')) ?></button>
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
  </main>
</div>
</body>
</html>