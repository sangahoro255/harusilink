<form method="post" action="<?= e(url('/lang')) ?>" class="lang-switch">
  <?= csrf_field() ?>
  <input type="hidden" name="next" value="<?= e(request_path()) ?>">
  <button type="submit" name="lang" value="sw" class="<?= current_lang() === 'sw' ? 'active' : '' ?>">SW</button>
  <button type="submit" name="lang" value="en" class="<?= current_lang() === 'en' ? 'active' : '' ?>">EN</button>
</form>