<header class="admin-bar">
  <img src="<?= e(asset('logos/harusilink-logo.png')) ?>" alt="HarusiLink">
  <nav class="admin-nav">
    <a href="<?= e(url('/dashboard')) ?>" class="<?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>"><?= e(t('owner.nav.dashboard')) ?></a>
    <a href="<?= e(url('/weddings/create')) ?>" class="<?= ($active ?? '') === 'create' ? 'active' : '' ?>"><?= e(t('owner.nav.new_wedding')) ?></a>
  </nav>
  <div class="actions">
    <?php view('partials/lang_switch'); ?>
    <form method="post" action="<?= e(url('/logout')) ?>">
      <?= csrf_field() ?>
      <button class="btn-outline" type="submit"><?= e(t('btn.logout')) ?></button>
    </form>
  </div>
</header>