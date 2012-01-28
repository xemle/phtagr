<h1><?php echo __('General'); ?></h1>

<?php echo $this->Session->flash(); ?>

<?php echo $this->Form->create(null, array('action' => 'index')); ?>
<fieldset><legend><?php echo __('General'); ?></legend>
<?php
  echo $this->Form->input('general.title', array('label' => __('Gallery title')));
  echo $this->Form->input('general.subtitle', array('label' => __('Gallery subtitle')));
?>
</fieldset>

<?php echo $this->Form->end(__('Save')); ?>
