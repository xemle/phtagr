<h1><?php echo __("Create Folder"); ?></h1>

<?php echo $this->Session->flash(); ?>

<p><?php echo __("Location %s", $this->FileList->location($path)); ?></p>

<?php echo $this->Form->create(false, array('url' => 'folder/'.$path)); ?>
<fieldset>
  <?php echo $this->Form->input('name', array ('label' => __("Folder Name"))); ?>
</fieldset>
<?php
  echo $this->Form->end(__('Create'));
?>
