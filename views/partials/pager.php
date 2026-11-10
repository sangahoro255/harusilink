<div class="pager">
  <?php if ($page > 1): ?>
    <a class="btn-outline" href="<?= e($pageUrl($page - 1)) ?>"><?= e(t('pager.prev')) ?></a>
  <?php else: ?>
    <span></span>
  <?php endif; ?>

  <span class="muted"><?= e(t('pager.page', ['page' => $page, 'pages' => $pages])) ?></span>

  <?php if ($page < $pages): ?>
    <a class="btn-outline" href="<?= e($pageUrl($page + 1)) ?>"><?= e(t('pager.next')) ?></a>
  <?php else: ?>
    <span></span>
  <?php endif; ?>
</div>