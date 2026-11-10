<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(t('auth.login.meta_title')) ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <?php view('partials/lang_switch'); ?>
    <div class="logo-box">
      <img src="<?= e(asset('logos/harusilink-logo.png')) ?>" alt="HarusiLink">
    </div>
    <h1><?= e(t('auth.login.title')) ?></h1>
    <p class="sub"><?= e(t('auth.login.subtitle')) ?></p>

    <?php if ($registered): ?>
      <div class="alert alert-success"><?= e(t('auth.login.registered_ok')) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <?= csrf_field() ?>

      <label for="email"><?= e(t('field.email')) ?></label>
      <input id="email" name="email" type="email" value="<?= e($old) ?>" autocomplete="email" required>

      <label for="password"><?= e(t('field.password')) ?></label>
      <input id="password" name="password" type="password" autocomplete="current-password" required>

      <button class="btn" type="submit"><?= e(t('auth.login.button')) ?></button>
    </form>

    <p class="link"><?= e(t('auth.login.no_account')) ?> <a href="<?= e(url('/register')) ?>"><?= e(t('auth.register.button')) ?></a></p>
  </div>
</div>
</body>
</html>