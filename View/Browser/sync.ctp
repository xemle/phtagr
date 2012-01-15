<h1><?php __("Meta Data Synchronization"); ?></h1>

<?php echo $session->flash(); ?>

<p><?php __("phTagr embedds meta data like tags, categories, or (geo) location information direct into the image video files."); ?></p>

<p><?php __("Note:  In case you are using phTagr with external paths it is recommended to synchronize your images to store this important information within the media file.  After the synchronization these mata data could be read by other image programs or desktop search engines."); ?>

<?php if ($this->data['action'] != 'run' && $this->data['unsynced'] > 0): ?>
<p><?php printf(__("You have %d unsynchronized media. Click %s to start the synchronization (this might take some time)", true), $this->data['unsynced'], $html->link(__("sync", true), 'sync/run')); ?></p>
<?php endif; ?>

<?php if ($this->data['unsynced'] == 0): ?>
<div class="info"><?php __("All media are synchronized"); ?></div>
<?php endif; ?>

<?php if (count($this->data['errors'])): ?>
<div class="error"><?php __("Some files could not be updated with new metadata. Mainly this happens if the files are write protected. Please have a look to the log files for details."); ?></div>
<?php endif; ?>

<?php if ($this->data['action'] == 'run'): ?>
<p><?php 
  printf(__("Synchronized %d media.", true), count($this->data['synced']));
  if ($this->data['unsynced']) {
    printf(__(" %d media remains unsynced. Click %s to synchronize again", true), $this->data['unsynced'], $html->link(__('sync', true), 'sync/run')); 
  }
?></p>
<?php endif; ?>
