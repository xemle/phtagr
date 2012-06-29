<h1><?php echo __('Database Upgrade'); ?></h1>
<?php echo $this->Session->flash(); ?>

<?php if ($currentVersion < $maxVersion): ?>
<p><?php echo __("Your database requires an update from version %d to %d!", $currentVersion, $maxVersion) . ' ' . $this->Html->link(__("Run database upgrade"), 'upgrade/run'); ?></p>
<p><?php echo __("Following database migrations will be executed:"); ?>
<ul>
<?php
  foreach ($newMappingNames as $name) {
    echo $this->Html->tag('li', $name);
  }
?>
</ul>
<?php else: ?>
<p><?php echo __("Your system has the newest database version %d.", $currentVersion); ?></p>
<?php endif; ?>

