<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(t('auth.register.meta_title')) ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <?php view('partials/lang_switch'); ?>
    <div class="logo-box">
      <img src="<?= e(asset('logos/harusilink-logo.png')) ?>" alt="HarusiLink">
    </div>
    <h1><?= e(t('auth.register.title')) ?></h1>
    <p class="sub"><?= e(t('auth.register.subtitle')) ?></p>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-danger"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" novalidate>
      <?= csrf_field() ?>

      <label for="name"><?= e(t('field.name')) ?></label>
      <input id="name" name="name" type="text" value="<?= e($old['name'] ?? '') ?>" autocomplete="name" required>

      <label for="email"><?= e(t('field.email')) ?></label>
      <input id="email" name="email" type="email" value="<?= e($old['email'] ?? '') ?>" autocomplete="email" required>

      <label for="phone"><?= e(t('field.phone_optional')) ?></label>
      <input id="phone" name="phone" type="tel" value="<?= e($old['phone'] ?? '') ?>" autocomplete="tel">

      <label for="password"><?= e(t('field.password_hint')) ?></label>
      <input id="password" name="password" type="password" autocomplete="new-password" required>

      <label for="password_confirm"><?= e(t('field.password_confirm')) ?></label>
      <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required>

      <button class="btn" type="submit"><?= e(t('auth.register.button')) ?></button>
    </form>

    <p class="link"><?= e(t('auth.register.have_account')) ?> <a href="<?= e(url('/login')) ?>"><?= e(t('auth.login.button')) ?></a></p>
  </div>
</div>
</body>
</html>