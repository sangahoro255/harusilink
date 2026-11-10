<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(t('wedding.create.meta_title')) ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/owner.css')) ?>">
</head>
<body>
<?php view('partials/owner_header', ['active' => 'create']); ?>

<main class="admin-main">
  <div class="form-card">
    <h1><?= e(t('wedding.create.title')) ?></h1>
    <p class="sub"><?= e(t('wedding.create.subtitle')) ?></p>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-danger"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" action="<?= e(url('/weddings')) ?>" novalidate>
      <?= csrf_field() ?>
      <?php view('weddings/_fields', ['old' => $old]); ?>
      <button class="btn" type="submit"><?= e(t('wedding.create.button')) ?></button>
    </form>
  </div>
</main>
</body>
</html>