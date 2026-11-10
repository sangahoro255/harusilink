<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e(t('inv.unavailable.title')) ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <?php view('partials/lang_switch'); ?>
    <div class="logo-box">
      <img src="<?= e(asset('logos/harusilink-logo.png')) ?>" alt="HarusiLink">
    </div>
    <h1><?= e(t('inv.unavailable.title')) ?></h1>
    <p class="sub"><?= e(t('inv.unavailable.text')) ?></p>
  </div>
</div>
</body>
</html>