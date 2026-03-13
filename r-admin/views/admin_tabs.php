<?php if ($tab === 'account'): ?>
  <?php require __DIR__ . '/account.php'; ?>
<?php elseif ($tab === 'posts'): ?>
  <?php require __DIR__ . '/posts.php'; ?>
<?php elseif ($tab === 'inbox'): ?>
  <?php require __DIR__ . '/inbox.php'; ?>
<?php elseif ($tab === 'studio'): ?>
  <?php require __DIR__ . '/studio.php'; ?>
<?php elseif ($tab === 'site'): ?>
  <?php require __DIR__ . '/site.php'; ?>
<?php elseif ($tab === 'extensions'): ?>
  <?php require __DIR__ . '/extensions.php'; ?>
<?php elseif ($tab === 'backups'): ?>
  <?php require __DIR__ . '/backups.php'; ?>
<?php elseif ($tab === 'media'): ?>
  <?php require __DIR__ . '/media.php'; ?>
<?php elseif ($tab === 'import'): ?>
  <?php require __DIR__ . '/import.php'; ?>
<?php elseif ($tab === 'audit'): ?>
  <?php require __DIR__ . '/audit.php'; ?>
<?php elseif ($tab === 'users'): ?>
  <?php require __DIR__ . '/users.php'; ?>
<?php else: ?>
  <?php require __DIR__ . '/pages.php'; ?>
<?php endif; ?>
