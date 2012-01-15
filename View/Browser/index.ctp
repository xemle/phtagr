<h1><?php echo __('File Browser'); ?></h1>

<?php echo $this->Session->flash(); ?>

<?php echo $this->Form->create('Browser', array('action' => 'import/'.$path)); ?>

<p><?php echo __("Location %s", $this->FileList->location($path)); ?>
<?php if ($isInternal) {
  echo __(" (%s or %s here)", 
    $this->Html->link(__("Upload files"), 'upload/'.$path),
    $this->Html->link(__("create folder"), 'folder/'.$path));
  } ?>. 

<?php echo $this->FileList->table($path, $dirs, $files, array('isInternal' => $isInternal)); ?>

<p><?php echo __("Location %s", $this->FileList->location($path)); ?>
<?php if ($isInternal) {
  echo __(" (%s or %s here)", 
    $this->Html->link(__("Upload files"), 'upload/'.$path),
    $this->Html->link(__("create folder"), 'folder/'.$path));
  } ?>. 
</p>

<?php 
  echo $this->Form->submit(__('Import'));
  echo $this->Form->end();
?>
