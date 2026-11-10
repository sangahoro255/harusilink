<?php $base = '/weddings/' . $wedding['id'] . '/messages'; ?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <?php view('partials/ws_head', ['title' => t('msg.inv.meta_title')]); ?>
</head>
<body>
<?php view('partials/owner_header', ['active' => '']); ?>

<div class="workspace">
  <?php view('partials/wedding_nav', ['wedding' => $wedding, 'active' => 'messages']); ?>

  <main class="ws-main">
    <div class="page-head">
      <div>
        <h1><?= e(t('msg.inv.title')) ?></h1>
        <p class="summary-line"><?= e(t('msg.inv.intro')) ?></p>
      </div>
      <a class="btn-outline" href="<?= e(url($base)) ?>"><?= e(t('msg.detail.back')) ?></a>
    </div>

    <?php view('partials/mode_banner', ['mode' => $mode]); ?>

    <?php if ($wedding['status'] !== 'active'): ?>
      <div class="alert alert-danger">
        <?= e(t('msg.err.not_active')) ?>
        <a href="<?= e(url('/weddings/' . $wedding['id'] . '/details')) ?>"><?= e(t('inv.draft_link')) ?></a>
      </div>
    <?php endif; ?>

    <?php if ($flash): ?>
      <div class="alert alert-success"><?= e(t($flash, $flashVars)) ?></div>
    <?php endif; ?>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-danger"><?= e($err) ?></div>
    <?php endforeach; ?>

    <div class="stat-grid">
      <div class="stat-card">
        <div class="num"><?= e((string) $counts['reachable']) ?></div>
        <div class="label"><?= e(t('msg.inv.reachable')) ?></div>
      </div>
      <div class="stat-card">
        <div class="num"><?= e((string) $counts['unsent']) ?></div>
        <div class="label"><?= e(t('msg.inv.unsent')) ?></div>
      </div>
      <div class="stat-card">
        <div class="num"><?= e((string) $counts['no_phone']) ?></div>
        <div class="label"><?= e(t('msg.inv.no_phone')) ?></div>
      </div>
    </div>

    <div class="form-card wide spaced-card">
      <h2><?= e(t('msg.inv.text_title')) ?></h2>
      <p class="muted"><?= e(t('msg.invite.text')) ?></p>

      <form method="post" action="<?= e(url($base . '/invitations')) ?>" novalidate
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
            <label for="scope"><?= e(t('msg.inv.scope')) ?></label>
            <select id="scope" name="scope">
              <option value="unsent" <?= $old['scope'] === 'unsent' ? 'selected' : '' ?>><?= e(t('msg.inv.scope_unsent')) ?> (<?= e((string) $counts['unsent']) ?>)</option>
              <option value="all" <?= $old['scope'] === 'all' ? 'selected' : '' ?>><?= e(t('msg.inv.scope_all')) ?> (<?= e((string) $counts['reachable']) ?>)</option>
            </select>
          </div>
        </div>

        <p class="hint"><?= e(t('msg.inv.limit', ['max' => $max])) ?></p>

        <button class="btn" type="submit" <?= $wedding['status'] === 'active' ? '' : 'disabled' ?>><?= e(t('msg.inv.send')) ?></button>
      </form>
    </div>
  </main>
</div>
</body>
</html>