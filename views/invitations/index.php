<?php
$base = '/weddings/' . $wedding['id'] . '/invitation';
$pageUrl = function (int $p) use ($base): string {
    return url($base) . ($p > 1 ? '?page=' . $p : '');
};
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(t('inv.meta_title')) ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/owner.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/workspace.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/guests.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/invitation.css')) ?>">
  <script src="<?= e(asset('js/copy.js')) ?>" defer></script>
</head>
<body>
<?php view('partials/owner_header', ['active' => '']); ?>

<div class="workspace">
  <?php view('partials/wedding_nav', ['wedding' => $wedding, 'active' => 'invitation']); ?>

  <main class="ws-main">
    <div class="page-head">
      <h1><?= e(t('inv.title')) ?></h1>
      <a class="btn-link" href="<?= e(url($base . '/design')) ?>"><?= e(t('inv.btn.design')) ?></a>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-success"><?= e(t($flash)) ?></div>
    <?php endif; ?>

    <?php if ($wedding['status'] === 'draft'): ?>
      <div class="alert alert-info">
        <?= e(t('inv.draft_note')) ?>
        <a href="<?= e(url('/weddings/' . $wedding['id'] . '/details')) ?>"><?= e(t('inv.draft_link')) ?></a>
      </div>
    <?php endif; ?>

    <h2 class="section-title"><?= e(t('inv.list.title')) ?></h2>
    <p class="muted"><?= e(t('inv.list.intro')) ?></p>

    <?php if ($total === 0): ?>
      <div class="empty">
        <?= e(t('inv.empty')) ?><br>
        <a href="<?= e(url('/weddings/' . $wedding['id'] . '/guests/create')) ?>"><?= e(t('inv.empty_link')) ?></a>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th><?= e(t('inv.col.guest')) ?></th>
              <th><?= e(t('inv.col.phone')) ?></th>
              <th><?= e(t('inv.col.link')) ?></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <?php $revoked = $r['revoked_at'] !== null; ?>
              <tr>
                <td data-label="<?= e(t('inv.col.guest')) ?>"><?= e($r['full_name']) ?></td>
                <td data-label="<?= e(t('inv.col.phone')) ?>"><?= e($r['phone'] ?: '—') ?></td>
                <td data-label="<?= e(t('inv.col.link')) ?>">
                  <span class="badge badge-<?= $revoked ? 'suspended' : 'active' ?>">
                    <?= e(t($revoked ? 'inv.link.revoked' : 'inv.link.active')) ?>
                  </span>
                </td>
                <td class="row-action">
                  <div class="row-actions">
                    <?php if (!$revoked): ?>
                      <button type="button" class="btn-outline"
                              data-copy-path="<?= e(url('/i/' . $r['token'])) ?>"
                              data-copied="<?= e(t('inv.btn.copied')) ?>"><?= e(t('inv.btn.copy')) ?></button>
                      <a class="btn-outline" href="<?= e(url('/i/' . $r['token'])) ?>" target="_blank" rel="noopener noreferrer"><?= e(t('inv.btn.open')) ?></a>
                      <a class="btn-outline" href="<?= e(url($base . '/' . $r['guest_id'] . '/qr')) ?>"><?= e(t('qr.owner.btn')) ?></a>
                    <?php endif; ?>

                    <form method="post" action="<?= e(url($base . '/' . $r['guest_id'] . '/toggle')) ?>">
                      <?= csrf_field() ?>
                      <input type="hidden" name="page" value="<?= e((string) $page) ?>">
                      <button type="submit" class="btn-small <?= $revoked ? 'btn-ok' : 'btn-danger' ?>">
                        <?= e(t($revoked ? 'inv.btn.restore' : 'inv.btn.revoke')) ?>
                      </button>
                    </form>

                    <form method="post" action="<?= e(url($base . '/' . $r['guest_id'] . '/regenerate')) ?>"
                          onsubmit="return confirm(<?= e(json_encode(t('inv.btn.confirm_regenerate'), JSON_UNESCAPED_UNICODE)) ?>)">
                      <?= csrf_field() ?>
                      <input type="hidden" name="page" value="<?= e((string) $page) ?>">
                      <button type="submit" class="btn-small btn-neutral"><?= e(t('inv.btn.regenerate')) ?></button>
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