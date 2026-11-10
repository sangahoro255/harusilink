<?php
$base = '/weddings/' . $wedding['id'] . '/guests';
$shown = $result ? array_slice($result['skipped'], 0, 100) : [];
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(t('import.meta_title')) ?></title>
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
    <div class="page-head">
      <h1><?= e(t('import.title')) ?></h1>
      <a class="btn-outline" href="<?= e(url($base)) ?>"><?= e(t('import.back')) ?></a>
    </div>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-danger"><?= e($err) ?></div>
    <?php endforeach; ?>

    <?php if ($result): ?>
      <?php if ($result['imported'] > 0): ?>
        <div class="alert alert-success"><?= e(t('import.result.imported', ['count' => $result['imported']])) ?></div>
      <?php else: ?>
        <div class="alert alert-info"><?= e(t('import.result.none')) ?></div>
      <?php endif; ?>

      <?php if ($result['skipped']): ?>
        <div class="alert alert-danger"><?= e(t('import.result.skipped', ['count' => count($result['skipped'])])) ?></div>
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th><?= e(t('import.col.row')) ?></th>
                <th><?= e(t('import.col.name')) ?></th>
                <th><?= e(t('import.col.reason')) ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($shown as $s): ?>
                <tr>
                  <td data-label="<?= e(t('import.col.row')) ?>"><?= e((string) $s['line']) ?></td>
                  <td data-label="<?= e(t('import.col.name')) ?>"><?= e($s['name'] !== '' ? $s['name'] : '—') ?></td>
                  <td data-label="<?= e(t('import.col.reason')) ?>"><?= e($s['reason']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if (count($result['skipped']) > count($shown)): ?>
          <p class="hint"><?= e(t('import.skipped_cap')) ?></p>
        <?php endif; ?>
      <?php endif; ?>

      <div class="actions-row">
        <a class="btn-link" href="<?= e(url($base)) ?>"><?= e(t('import.view_guests')) ?></a>
      </div>
    <?php endif; ?>

    <div class="form-card">
      <h2><?= e(t('import.steps.title')) ?></h2>
      <ol class="steps">
        <li><?= e(t('import.step1')) ?></li>
        <li><?= e(t('import.step2')) ?></li>
        <li><?= e(t('import.step3')) ?></li>
        <li><?= e(t('import.step4')) ?></li>
      </ol>
      <a class="btn-outline" href="<?= e(url($base . '/import/template')) ?>"><?= e(t('import.download')) ?></a>

      <h2 class="spaced"><?= e(t('import.format.title')) ?></h2>
      <ol class="steps">
        <li><span class="code">name</span> – <?= e(t('import.format.name')) ?></li>
        <li><span class="code">phone</span> – <?= e(t('import.format.phone')) ?></li>
        <li><span class="code">email</span> – <?= e(t('import.format.email')) ?></li>
        <li><span class="code">seats</span> – <?= e(t('import.format.seats')) ?></li>
        <li><span class="code">category</span> – <?= e(t('import.format.category')) ?></li>
      </ol>
      <p class="hint"><?= e(t('import.rules')) ?></p>

      <form method="post" action="<?= e(url($base . '/import')) ?>" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>
        <label for="file"><?= e(t('import.field.file')) ?></label>
        <input id="file" name="file" type="file" accept=".csv,text/csv,text/plain" required>
        <button class="btn" type="submit"><?= e(t('import.button')) ?></button>
      </form>
    </div>
  </main>
</div>
</body>
</html>