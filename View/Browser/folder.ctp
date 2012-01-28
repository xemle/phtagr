<h1><?php echo __("Create Folder"); ?></h1>

<?php echo $this->Session->flash(); ?>

<p><?php echo __("Location %s", $this->FileList->location($path)); ?></p>

<?php echo $this->Form->create(false, array('action' => 'folder/'.$path)); ?>
<fieldset>
  <?php echo $this->Form->input('Folder.name', array ('label' => __("Folder Name"))); ?>
</fieldset>
<?php 
  echo $this->Html->tag('ul', 
    $this->Html->tag('li', $this->Form->submit(__('Create')), array('escape' => false)),
    array('class' => 'buttons', 'escape' => false));
  echo $this->Form->end();
?>
