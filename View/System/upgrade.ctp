<h1><?php echo __('Database Upgrade'); ?></h1>
<?php echo $this->Session->flash(); ?>

<?php if ($currentVersion < $maxVersion): ?>
<p><?php echo __("Your database requires an update from version $currentVersion to $maxVersion! Please click %s to run to upgrade.", $this->Html->link(__("here"), 'upgrade/run')); ?></p>
<p><?php echo __("Following database migrations will be done:"); ?>
<ul>
<?php 
  foreach ($newMappingNames as $name) {
    echo $this->Html->tag('li', $name);
  }
?>
</ul>
<?php else: ?>
<p><?php echo __("Your system has the newest database version $currentVersion."); ?></p>
<?php endif; ?>

