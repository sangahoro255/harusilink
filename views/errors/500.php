<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e(t('sys.500.title')) ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/modules.css')) ?>">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="logo-box">
      <img src="<?= e(asset('logos/harusilink-logo.png')) ?>" alt="HarusiLink">
    </div>
    <h1><?= e(t('sys.500.title')) ?></h1>
    <p class="sub"><?= e(t('sys.500.text')) ?></p>
    <?php if (!empty($debugMessage)): ?>
      <pre class="debug-box"><?= e($debugMessage) ?></pre>
    <?php endif; ?>
    <p class="link"><a href="<?= e(url('/dashboard')) ?>"><?= e(t('sys.500.home')) ?></a></p>
  </div>
</div>
</body>
</html>