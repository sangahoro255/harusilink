<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(t('wedding.edit.meta_title')) ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/owner.css')) ?>">
</head>
<body>
<?php view('partials/owner_header', ['active' => '']); ?>

<main class="admin-main">
  <div class="form-card">
    <h1><?= e(t('wedding.edit.title')) ?></h1>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-danger"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" action="<?= e(url('/weddings/' . $wedding['id'] . '/update')) ?>" enctype="multipart/form-data" novalidate>
      <?= csrf_field() ?>
      <?php view('weddings/_fields', ['old' => $old]); ?>

      <label for="cover"><?= e(t('wedding.field.cover')) ?></label>
      <?php if ($wedding['cover_image']): ?>
        <img class="cover-preview" src="<?= e(url('/uploads/covers/' . $wedding['cover_image'])) ?>" alt="<?= e(t('wedding.cover.current')) ?>">
      <?php endif; ?>
      <input id="cover" name="cover" type="file" accept="image/jpeg,image/png,image/webp">
      <p class="hint"><?= e(t('wedding.field.cover_hint')) ?></p>

      <button class="btn" type="submit"><?= e(t('wedding.edit.button')) ?></button>
      <div class="actions-row">
        <a class="btn-outline" href="<?= e(url('/weddings/' . $wedding['id'])) ?>"><?= e(t('wedding.edit.cancel')) ?></a>
      </div>
    </form>
  </div>
</main>
</body>
</html>