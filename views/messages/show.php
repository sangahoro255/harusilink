<?php
$base = '/weddings/' . $wedding['id'] . '/messages';
$badge = ['queued' => 'neutral', 'sent' => 'done', 'delivered' => 'active', 'read' => 'active', 'failed' => 'suspended'];
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <?php view('partials/ws_head', ['title' => t('msg.meta_title')]); ?>
</head>
<body>
<?php view('partials/owner_header', ['active' => '']); ?>

<div class="workspace">
  <?php view('partials/wedding_nav', ['wedding' => $wedding, 'active' => 'messages']); ?>

  <main class="ws-main">
    <div class="page-head">
      <h1><?= e(t('msg.detail.title')) ?></h1>
      <a class="btn-outline" href="<?= e(url($base)) ?>"><?= e(t('msg.detail.back')) ?></a>
    </div>

    <div class="form-card wide">
      <p class="muted"><?= e(t('msg.kind.' . $message['kind'])) ?> · <?= e(t('msg.channel.' . $message['channel'])) ?> · <?= e(substr($message['created_at'], 0, 16)) ?></p>
      <p><?= nl2br(e((string) $message['body'])) ?></p>
    </div>

    <h2 class="section-title"><?= e(t('msg.detail.recipients')) ?></h2>

    <?php if (!$logs): ?>
      <div class="empty"><?= e(t('msg.detail.none')) ?></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th><?= e(t('msg.col.name')) ?></th>
              <th><?= e(t('msg.col.phone')) ?></th>
              <th><?= e(t('msg.col.status')) ?></th>
              <th><?= e(t('msg.col.error')) ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($logs as $l): ?>
              <tr>
                <td data-label="<?= e(t('msg.col.name')) ?>"><?= e($l['name'] ?: '—') ?></td>
                <td data-label="<?= e(t('msg.col.phone')) ?>"><?= e($l['recipient']) ?></td>
                <td data-label="<?= e(t('msg.col.status')) ?>">
                  <span class="badge badge-<?= e($badge[$l['status']] ?? 'neutral') ?>"><?= e(t('msg.status.' . $l['status'])) ?></span>
                  <?php if ($l['provider'] === 'mock'): ?><span class="tag"><?= e(t('msg.tag.sim')) ?></span><?php endif; ?>
                </td>
                <td data-label="<?= e(t('msg.col.error')) ?>"><?= e($l['error'] ?: '—') ?></td>
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