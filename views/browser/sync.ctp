<h1><?php __("File Synchronization"); ?></h1>

<?php if ($this->data['errors']): ?>
<div class="error"><?php __("Some files could not be updated with new metadata. Mainly this happens if the files are write protected. Please have a look to the log files for details."); ?></div>
<?php endif; ?>

<?php if ($this->data['total'] > 0): ?>
<p><?php 
  printf(__("Synchronized %d of %d media", true), $this->data['synced'], $this->data['total']);
  if ($this->data['unsynced']) {
    printf(__(" %d media remains unsynced. %s", true), $html->link(__('Synchronize again?', true), 'sync')); 
  }
?></p>
<?php else: ?>
<div class="info"><?php __("All media are synchronized"); ?></div>
<?php endif; ?>
