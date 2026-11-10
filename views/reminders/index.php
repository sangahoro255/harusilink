<?php
use App\Helpers\Money;

$base = '/weddings/' . $wedding['id'] . '/reminders';
$badge = ['queued' => 'neutral', 'sent' => 'done', 'delivered' => 'active', 'read' => 'active', 'failed' => 'suspended'];
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <?php view('partials/ws_head', ['title' => t('rem.meta_title')]); ?>
</head>
<body>
<?php view('partials/owner_header', ['active' => '']); ?>

<div class="workspace">
  <?php view('partials/wedding_nav', ['wedding' => $wedding, 'active' => 'reminders']); ?>

  <main class="ws-main">
    <div class="page-head">
      <div>
        <h1><?= e(t('rem.title')) ?></h1>
        <p class="summary-line"><?= e(t('rem.intro', ['days' => $days])) ?></p>
      </div>
    </div>

    <?php view('partials/mode_banner', ['mode' => $mode]); ?>

    <?php if ($flash): ?>
      <div class="alert alert-success"><?= e(t($flash, $flashVars)) ?></div>
    <?php endif; ?>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-danger"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" action="<?= e(url($base)) ?>" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="nonce" value="<?= e($nonce) ?>">

      <div class="form-card wide">
        <h2><?= e(t('rem.compose.title')) ?></h2>

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
        </div>

        <label for="body"><?= e(t('msg.field.body')) ?></label>
        <textarea id="body" name="body" rows="4" maxlength="500" required><?= e($old['body']) ?></textarea>
        <p class="hint"><?= e(t('rem.field.hint')) ?></p>

        <div class="panel-row">
          <button class="btn-link" type="submit" name="audience" value="pledged_all"
                  onclick="return confirm(<?= e(json_encode(t('msg.compose.confirm'), JSON_UNESCAPED_UNICODE)) ?>)"><?= e(t('rem.btn.all_pledged', ['count' => count($pledged)])) ?></button>
          <button class="btn-outline" type="submit" name="audience" value="selected"
                  onclick="return confirm(<?= e(json_encode(t('msg.compose.confirm'), JSON_UNESCAPED_UNICODE)) ?>)"><?= e(t('rem.btn.selected')) ?></button>
          <button class="btn-outline" type="submit" name="audience" value="no_contribution"
                  onclick="return confirm(<?= e(json_encode(t('msg.compose.confirm'), JSON_UNESCAPED_UNICODE)) ?>)"><?= e(t('rem.btn.no_contribution', ['count' => $noContribution])) ?></button>
        </div>
      </div>

      <h2 class="section-title"><?= e(t('rem.pledged.title')) ?></h2>

      <?php if (!$pledged): ?>
        <div class="empty"><?= e(t('rem.pledged.empty')) ?></div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th class="check-col"><input type="checkbox" data-select-all=".pick" aria-label="<?= e(t('rem.select_all')) ?>"></th>
                <th><?= e(t('contrib.col.name')) ?></th>
                <th><?= e(t('contrib.col.amount')) ?></th>
                <th><?= e(t('rem.col.last')) ?></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pledged as $p): ?>
                <?php $cooling = $p['reminded_at'] !== null; ?>
                <tr>
                  <td class="check-col" data-label="">
                    <input class="pick" type="checkbox" name="ids[]" value="<?= e((string) $p['id']) ?>" <?= $cooling ? 'disabled' : '' ?>>
                  </td>
                  <td data-label="<?= e(t('contrib.col.name')) ?>">
                    <?= e($p['contributor_name']) ?>
                    <span class="sub"><?= e($p['phone']) ?></span>
                  </td>
                  <td data-label="<?= e(t('contrib.col.amount')) ?>"><strong><?= e(Money::format($p['amount'])) ?></strong></td>
                  <td data-label="<?= e(t('rem.col.last')) ?>">
                    <?= e($cooling ? substr($p['reminded_at'], 0, 16) : '—') ?>
                  </td>
                  <td class="row-action">
                    <button type="submit" name="only" value="<?= e((string) $p['id']) ?>" class="btn-small btn-neutral" <?= $cooling ? 'disabled' : '' ?>>
                      <?= e(t('rem.btn.remind')) ?>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </form>

    <h2 class="section-title"><?= e(t('rem.history.title')) ?></h2>

    <?php if (!$history): ?>
      <div class="empty"><?= e(t('rem.history.empty')) ?></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th><?= e(t('msg.col.name')) ?></th>
              <th><?= e(t('msg.col.phone')) ?></th>
              <th><?= e(t('msg.col.status')) ?></th>
              <th><?= e(t('msg.col.time')) ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($history as $h): ?>
              <tr>
                <td data-label="<?= e(t('msg.col.name')) ?>"><?= e($h['name'] ?: '—') ?></td>
                <td data-label="<?= e(t('msg.col.phone')) ?>"><?= e($h['recipient']) ?></td>
                <td data-label="<?= e(t('msg.col.status')) ?>">
                  <span class="badge badge-<?= e($badge[$h['status']] ?? 'neutral') ?>"><?= e(t('msg.status.' . $h['status'])) ?></span>
                </td>
                <td data-label="<?= e(t('msg.col.time')) ?>"><?= e(substr($h['created_at'], 0, 16)) ?></td>
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