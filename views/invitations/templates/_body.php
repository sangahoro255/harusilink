<?php
use App\Helpers\Format;

$time = Format::time($wedding['wedding_time']);
?>
<?php if ($guestName !== null): ?>
  <p class="inv-guest"><?= e(t('inv.card.dear', ['name' => $guestName])) ?></p>
  <?php if ($seats > 1): ?>
    <p class="inv-seats"><?= e(t('inv.card.seats', ['seats' => $seats])) ?></p>
  <?php endif; ?>
<?php endif; ?>

<p class="inv-message"><?= nl2br(e($message)) ?></p>

<dl class="inv-details">
  <div>
    <dt><?= e(t('inv.card.date')) ?></dt>
    <dd><?= e(Format::date($wedding['wedding_date'])) ?></dd>
  </div>
  <?php if ($time !== ''): ?>
    <div>
      <dt><?= e(t('inv.card.time')) ?></dt>
      <dd><?= e($time) ?></dd>
    </div>
  <?php endif; ?>
  <div>
    <dt><?= e(t('inv.card.venue')) ?></dt>
    <dd><?= e($wedding['venue']) ?></dd>
  </div>
</dl>

<?php if (!empty($wedding['description'])): ?>
  <p class="inv-extra"><?= nl2br(e($wedding['description'])) ?></p>
<?php endif; ?>