<?php $base = '/weddings/' . $wedding['id'] . '/contributions'; ?>
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
      <h1><?= e(t('contrib.settings.title')) ?></h1>
      <p class="sub"><?= e(t('contrib.settings.intro')) ?></p>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger"><?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="post" action="<?= e(url($base . '/settings')) ?>" novalidate>
        <?= csrf_field() ?>

        <div class="form-row">
          <div>
            <label for="target"><?= e(t('contrib.settings.target')) ?></label>
            <input id="target" name="target" type="text" inputmode="decimal" value="<?= e($old['target']) ?>">
          </div>
          <div>
            <label for="deadline"><?= e(t('contrib.settings.deadline')) ?></label>
            <input id="deadline" name="deadline" type="date" value="<?= e($old['deadline']) ?>">
          </div>
        </div>

        <label for="description"><?= e(t('contrib.settings.description')) ?></label>
        <textarea id="description" name="description" rows="3" maxlength="1000"><?= e($old['description']) ?></textarea>

        <label for="payment_info"><?= e(t('contrib.settings.payment')) ?></label>
        <textarea id="payment_info" name="payment_info" rows="4" maxlength="1000"><?= e($old['payment_info']) ?></textarea>
        <p class="hint"><?= e(t('contrib.settings.payment_hint')) ?></p>

        <button class="btn" type="submit"><?= e(t('contrib.settings.save')) ?></button>
        <div class="actions-row">
          <a class="btn-outline" href="<?= e(url($base)) ?>"><?= e(t('contrib.form.cancel')) ?></a>
        </div>
      </form>
    </div>
  </main>
</div>
</body>
</html>