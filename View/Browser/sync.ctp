<h1><?php echo __("Meta Data Synchronization"); ?></h1>

<?php echo $this->Session->flash(); ?>

<p><?php echo __("phTagr embedds meta data like tags, categories, or (geo) location information direct into the image video files."); ?></p>

<p><?php echo __("Note:  In case you are using phTagr with external paths it is recommended to synchronize your images to store this important information within the media file.  After the synchronization these mata data could be read by other image programs or desktop search engines."); ?>

<?php if ($this->data['action'] != 'run' && $this->data['unsynced'] > 0): ?>
<p><?php echo __("You have %d unsynchronized media. Click %s to start the synchronization (this might take some time)", $this->data['unsynced'], $this->Html->link(__("sync", true), 'sync/run')); ?></p>
<?php endif; ?>

<?php if ($this->data['unsynced'] == 0): ?>
<div class="info"><?php echo __("All media are synchronized"); ?></div>
<?php endif; ?>

<?php if (count($this->data['errors'])): ?>
<div class="error"><?php echo __("Some files could not be updated with new metadata. Mainly this happens if the files are write protected. Please have a look to the log files for details."); ?></div>
<?php endif; ?>

<?php if ($this->data['action'] == 'run'): ?>
<p><?php 
  echo __("Synchronized %d media.", count($this->data['synced']));
  if ($this->data['unsynced']) {
    echo __(" %d media remains unsynced. Click %s to synchronize again", $this->data['unsynced'], $this->Html->link(__('sync', 'sync/run'))); 
  }
?></p>
<?php endif; ?>
