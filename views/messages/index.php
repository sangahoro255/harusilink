<?php
$base = '/weddings/' . $wedding['id'] . '/messages';
$pageUrl = function (int $p) use ($base): string {
    return url($base) . ($p > 1 ? '?page=' . $p : '');
};
$audiences = ['all' => $counts['all'], 'accepted' => $counts['accepted'], 'pending' => $counts['pending'], 'declined' => $counts['declined']];
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
      <div>
        <h1><?= e(t('msg.title')) ?></h1>
        <p class="summary-line"><?= e(t('msg.intro')) ?></p>
      </div>
      <a class="btn-link" href="<?= e(url($base . '/invitations')) ?>"><?= e(t('msg.btn.send_invitations')) ?></a>
    </div>

    <?php view('partials/mode_banner', ['mode' => $mode]); ?>

    <?php if ($flash): ?>
      <div class="alert alert-success"><?= e(t($flash, $flashVars)) ?></div>
    <?php endif; ?>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-danger"><?= e($err) ?></div>
    <?php endforeach; ?>

    <div class="form-card wide">
      <h2><?= e(t('msg.compose.title')) ?></h2>

      <form method="post" action="<?= e(url($base)) ?>" novalidate
            onsubmit="return confirm(<?= e(json_encode(t('msg.compose.confirm'), JSON_UNESCAPED_UNICODE)) ?>)">
        <?= csrf_field() ?>
        <input type="hidden" name="nonce" value="<?= e($nonce) ?>">

        <div class="form-row">
          <div>
            <label for="channel"><?= e(t('msg.field.channel')) ?></label>
            <select id="channel" name="channel">
              <?php foreach (['whatsapp', 'sms'] as $c): ?>
                <option value="<?= e($c) ?>" <?= $old['channel'] === $c ? 'selected' : '' ?> <?= $channels[$c] ? '' : 'disabled' ?>>
                  <?= e(t('msg.channel.' . $c)) ?><?= $channels[$c] ? '' : ' (' . e(t('msg.channel.unavailable')) . ')' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="audience"><?= e(t('msg.field.audience')) ?></label>
            <select id="audience" name="audience">
              <?php foreach ($audiences as $key => $count): ?>
                <option value="<?= e($key) ?>" <?= $old['audience'] === $key ? 'selected' : '' ?>>
                  <?= e(t('msg.audience.' . $key)) ?> (<?= e((string) $count) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <label for="body"><?= e(t('msg.field.body')) ?></label>
        <textarea id="body" name="body" rows="5" maxlength="500" required><?= e($old['body']) ?></textarea>
        <p class="hint"><?= e(t('msg.field.body_hint', ['max' => $max])) ?></p>

        <?php if ($counts['no_phone'] > 0): ?>
          <p class="hint"><?= e(t('msg.no_phone', ['count' => $counts['no_phone']])) ?></p>
        <?php endif; ?>

        <button class="btn" type="submit"><?= e(t('msg.compose.send')) ?></button>
      </form>
    </div>

    <h2 class="section-title"><?= e(t('msg.history.title')) ?></h2>

    <?php if (!$rows): ?>
      <div class="empty"><?= e(t('msg.history.empty')) ?></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th><?= e(t('msg.col.message')) ?></th>
              <th><?= e(t('msg.col.kind')) ?></th>
              <th><?= e(t('msg.col.recipients')) ?></th>
              <th><?= e(t('msg.col.delivered')) ?></th>
              <th><?= e(t('msg.col.failed')) ?></th>
              <th><?= e(t('msg.col.time')) ?></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td data-label="<?= e(t('msg.col.message')) ?>">
                  <?= e(mb_strimwidth((string) $r['body'], 0, 70, '…')) ?>
                  <?php if ($r['provider'] === 'mock'): ?><span class="tag"><?= e(t('msg.tag.sim')) ?></span><?php endif; ?>
                </td>
                <td data-label="<?= e(t('msg.col.kind')) ?>"><?= e(t('msg.kind.' . $r['kind'])) ?> · <?= e(t('msg.channel.' . $r['channel'])) ?></td>
                <td data-label="<?= e(t('msg.col.recipients')) ?>"><?= e((string) $r['recipients_count']) ?></td>
                <td data-label="<?= e(t('msg.col.delivered')) ?>"><?= e((string) $r['delivered']) ?></td>
                <td data-label="<?= e(t('msg.col.failed')) ?>"><?= e((string) $r['failed']) ?></td>
                <td data-label="<?= e(t('msg.col.time')) ?>"><?= e(substr($r['created_at'], 0, 16)) ?></td>
                <td class="row-action">
                  <a class="btn-outline" href="<?= e(url($base . '/' . $r['id'])) ?>"><?= e(t('msg.btn.details')) ?></a>
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