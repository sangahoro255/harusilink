<?php $couple = $wedding['bride_name'] . ' & ' . $wedding['groom_name']; ?>
<article class="inv-card inv-tpl-modern" style="<?= e($style) ?>">
  <?php if (!empty($wedding['cover_image'])): ?>
    <img class="inv-cover" src="<?= e(url('/uploads/covers/' . $wedding['cover_image'])) ?>" alt="<?= e($couple) ?>">
  <?php endif; ?>

  <header class="inv-band">
    <p class="inv-kicker"><?= e(t('inv.card.kicker')) ?></p>
    <h2 class="inv-names">
      <span><?= e($wedding['bride_name']) ?></span>
      <span class="inv-amp">&amp;</span>
      <span><?= e($wedding['groom_name']) ?></span>
    </h2>
  </header>

  <div class="inv-body">
    <?php view('invitations/templates/_body', [
        'wedding'   => $wedding,
        'guestName' => $guestName,
        'seats'     => $seats,
        'message'   => $message,
    ]); ?>
  </div>
</article>