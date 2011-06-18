<h1><?php __('Database Upgrade'); ?></h1>
<?php echo $session->flash(); ?>

<?php if ($currentVersion < $maxVersion): ?>
<p><?php printf(__("Your database requires an update from version $currentVersion to $maxVersion! Please click %s to run to upgrade.", true), $html->link(__("here", true), 'upgrade/run')); ?></p>
<p><?php __("Following database migrations will be done:"); ?>
<ul>
<?php 
  foreach ($newMappingNames as $name) {
    echo $html->tag('li', $name);
  }
?>
</ul>
<?php else: ?>
<p><?php __("Your system has the newest database version $currentVersion."); ?></p>
<?php endif; ?>

