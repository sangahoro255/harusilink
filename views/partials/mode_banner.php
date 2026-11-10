<?php if ($mode === 'mock'): ?>
  <div class="mode-banner sim">
    <strong><?= e(t('msg.mode.sim_title')) ?></strong>
    <?= e(t('msg.mode.sim_text')) ?>
  </div>
<?php else: ?>
  <div class="mode-banner live">
    <strong><?= e(t('msg.mode.live_title')) ?></strong>
    <?= e(t('msg.mode.live_text')) ?>
  </div>
<?php endif; ?>