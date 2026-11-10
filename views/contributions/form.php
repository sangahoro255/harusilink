<?php
$base = '/weddings/' . $wedding['id'] . '/contributions';
$isEdit = $contribution !== null;
$action = $isEdit ? $base . '/' . $contribution['id'] . '/update' : $base;
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
    <div class="form-card">
      <h1><?= e(t($isEdit ? 'contrib.form.edit_title' : 'contrib.form.create_title')) ?></h1>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger"><?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="post" action="<?= e(url($action)) ?>" novalidate>
        <?= csrf_field() ?>

        <label for="contributor_name"><?= e(t('contrib.field.name')) ?></label>
        <input id="contributor_name" name="contributor_name" type="text" maxlength="120" value="<?= e($old['contributor_name']) ?>" required>

        <div class="form-row">
          <div>
            <label for="amount"><?= e(t('contrib.field.amount')) ?></label>
            <input id="amount" name="amount" type="text" inputmode="decimal" value="<?= e($old['amount']) ?>" required>
          </div>
          <div>
            <label for="phone"><?= e(t('contrib.field.phone_optional')) ?></label>
            <input id="phone" name="phone" type="tel" maxlength="25" value="<?= e($old['phone']) ?>" autocomplete="off">
          </div>
        </div>

        <div class="form-row">
          <div>
            <label for="status"><?= e(t('contrib.field.status')) ?></label>
            <select id="status" name="status">
              <?php foreach (['paid', 'pledged', 'cancelled'] as $s): ?>
                <option value="<?= e($s) ?>" <?= $old['status'] === $s ? 'selected' : '' ?>><?= e(t('contrib.status.' . $s)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="method"><?= e(t('contrib.field.method')) ?></label>
            <select id="method" name="method">
              <?php foreach (['cash', 'mobile_money', 'bank', 'other'] as $m): ?>
                <option value="<?= e($m) ?>" <?= $old['method'] === $m ? 'selected' : '' ?>><?= e(t('contrib.method.' . $m)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div>
            <label for="contributed_on"><?= e(t('contrib.field.date')) ?></label>
            <input id="contributed_on" name="contributed_on" type="date" value="<?= e($old['contributed_on']) ?>" required>
          </div>
          <div>
            <label for="reference"><?= e(t('contrib.field.reference_optional')) ?></label>
            <input id="reference" name="reference" type="text" maxlength="80" value="<?= e($old['reference']) ?>" autocomplete="off">
          </div>
        </div>

        <label for="note"><?= e(t('contrib.field.note_optional')) ?></label>
        <input id="note" name="note" type="text" maxlength="255" value="<?= e($old['note']) ?>">

        <button class="btn" type="submit"><?= e(t('contrib.form.save')) ?></button>
        <div class="actions-row">
          <a class="btn-outline" href="<?= e(url($base)) ?>"><?= e(t('contrib.form.cancel')) ?></a>
        </div>
      </form>
    </div>
  </main>
</div>
</body>
</html>