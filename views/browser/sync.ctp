<h1>File Synchronization</h1>

<?php if ($this->data['errors']): ?>
<div class="error">Some files could not be updated with new metadata. Mainly this happens if the files are write protected. Please have a look to the log files for details.</div>
<?php endif; ?>

<?php if ($this->data['total'] > 0): ?>
<?php 
  echo "Synchronized {$this->data['synced']} of {$this->data['total']} images.";
  if ($this->data['unsynced']) {
    echo " {$this->data['unsynced']} images remains unsynced. ".$html->link('Synchronize again?', 'sync'); 
  }
?>
<?php else: ?>
<div class="info">All images are synchronized</div>
<?php endif; ?>
