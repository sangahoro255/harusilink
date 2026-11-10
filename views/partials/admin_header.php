<header class="admin-bar">
  <img src="<?= e(asset('logos/harusilink-logo.png')) ?>" alt="HarusiLink">
  <nav class="admin-nav">
    <a href="<?= e(url('/admin')) ?>" class="<?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>"><?= e(t('admin.nav.dashboard')) ?></a>
    <a href="<?= e(url('/admin/users')) ?>" class="<?= ($active ?? '') === 'users' ? 'active' : '' ?>"><?= e(t('admin.nav.users')) ?></a>
    <a href="<?= e(url('/admin/weddings')) ?>" class="<?= ($active ?? '') === 'weddings' ? 'active' : '' ?>"><?= e(t('admin.nav.weddings')) ?></a>
    <a href="<?= e(url('/admin/activity')) ?>" class="<?= ($active ?? '') === 'activity' ? 'active' : '' ?>"><?= e(t('admin.nav.activity')) ?></a>
  </nav>
  <div class="actions">
    <?php view('partials/lang_switch'); ?>
    <form method="post" action="<?= e(url('/logout')) ?>">
      <?= csrf_field() ?>
      <button class="btn-outline" type="submit"><?= e(t('btn.logout')) ?></button>
    </form>
  </div>
</header>