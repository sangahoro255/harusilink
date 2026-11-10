<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(t('dashboard.meta_title')) ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <?php view('partials/lang_switch'); ?>
    <div class="logo-box">
      <img src="<?= e(asset('logos/harusilink-logo.png')) ?>" alt="HarusiLink">
    </div>
    <h1><?= e(t('dashboard.welcome', ['name' => $user['name']])) ?></h1>
    <p class="sub"><?= e(t('dashboard.signed_in_as', ['email' => $user['email']])) ?></p>

    <form method="post" action="<?= e(url('/logout')) ?>">
      <?= csrf_field() ?>
      <button class="btn" type="submit"><?= e(t('btn.logout')) ?></button>
    </form>
  </div>
</div>
</body>
</html>