<?php
$base = '/weddings/' . $wedding['id'];
$items = [
    ['key' => 'dashboard',     'path' => $base,                    'label' => 'ws.nav.dashboard'],
    ['key' => 'details',       'path' => $base . '/details',       'label' => 'ws.nav.details'],
    ['key' => 'invitation',    'path' => $base . '/invitation',    'label' => 'ws.nav.invitation'],
    ['key' => 'guests',        'path' => $base . '/guests',        'label' => 'ws.nav.guests'],
    ['key' => 'rsvp',          'path' => $base . '/rsvp',          'label' => 'ws.nav.rsvp'],
    ['key' => 'contributions', 'path' => $base . '/contributions', 'label' => 'ws.nav.contributions'],
    ['key' => 'reminders',     'path' => $base . '/reminders',     'label' => 'ws.nav.reminders'],
    ['key' => 'messages',      'path' => $base . '/messages',      'label' => 'ws.nav.messages'],
    ['key' => 'checkin',       'path' => $base . '/checkin',       'label' => 'ws.nav.checkin'],
    ['key' => 'reports',       'path' => $base . '/reports',       'label' => 'ws.nav.reports'],
    ['key' => 'settings',      'path' => null,                     'label' => 'ws.nav.settings'],
];
?>
<nav class="ws-nav" aria-label="<?= e(t('ws.nav.label')) ?>">
  <ul>
    <?php foreach ($items as $item): ?>
      <li>
        <?php if ($item['path'] !== null): ?>
          <a href="<?= e(url($item['path'])) ?>" class="<?= ($active ?? '') === $item['key'] ? 'active' : '' ?>"><?= e(t($item['label'])) ?></a>
        <?php else: ?>
          <span class="soon"><?= e(t($item['label'])) ?> <span class="tag"><?= e(t('ws.soon')) ?></span></span>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
</nav>