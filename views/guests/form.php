<?php
$base = '/weddings/' . $wedding['id'] . '/guests';
$isEdit = $guest !== null;
$action = $isEdit ? $base . '/' . $guest['id'] . '/update' : $base;
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(t('guest.form.meta_title')) ?></title>
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
    <div class="form-card">
      <h1><?= e(t($isEdit ? 'guest.form.edit_title' : 'guest.form.create_title')) ?></h1>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger"><?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="post" action="<?= e(url($action)) ?>" novalidate>
        <?= csrf_field() ?>

        <label for="full_name"><?= e(t('guest.field.name')) ?></label>
        <input id="full_name" name="full_name" type="text" maxlength="120" value="<?= e($old['full_name']) ?>" required>

        <div class="form-row">
          <div>
            <label for="phone"><?= e(t('guest.field.phone_optional')) ?></label>
            <input id="phone" name="phone" type="tel" maxlength="25" value="<?= e($old['phone']) ?>" autocomplete="off">
            <p class="hint"><?= e(t('guest.field.phone_hint')) ?></p>
          </div>
          <div>
            <label for="email"><?= e(t('guest.field.email_optional')) ?></label>
            <input id="email" name="email" type="email" maxlength="190" value="<?= e($old['email']) ?>" autocomplete="off">
          </div>
        </div>

        <div class="form-row">
          <div>
            <label for="seats"><?= e(t('guest.field.seats')) ?></label>
            <input id="seats" name="seats" type="number" min="1" max="20" value="<?= e($old['seats']) ?>" required>
            <p class="hint"><?= e(t('guest.field.seats_hint')) ?></p>
          </div>
          <div>
            <label for="category_id"><?= e(t('guest.field.category')) ?></label>
            <select id="category_id" name="category_id">
              <option value=""><?= e(t('guest.field.no_category')) ?></option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= e((string) $c['id']) ?>" <?= $old['category_id'] === (string) $c['id'] ? 'selected' : '' ?>>
                  <?= e($c['name'] ?: t('cat.' . $c['code'])) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <button class="btn" type="submit"><?= e(t($isEdit ? 'guest.form.save_button' : 'guest.form.create_button')) ?></button>
        <div class="actions-row">
          <a class="btn-outline" href="<?= e(url($base)) ?>"><?= e(t('guest.form.cancel')) ?></a>
        </div>
      </form>
    </div>
  </main>
</div>
</body>
</html>