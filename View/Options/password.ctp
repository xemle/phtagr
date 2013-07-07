<h1><?php echo __('Profile'); ?></h1>

<?php echo $this->Session->flash(); ?>

<?php echo $this->Form->create(null, array('url' => 'password')); ?>

<fieldset><legend><?php echo __('Password'); ?></legend>
<?php
  echo $this->Form->input('User.password', array('label' => __('Password')));
  echo $this->Form->input('User.confirm', array('type' => 'password', 'label' => __('Confirm')));
?>
</fieldset>
<?php echo $this->Form->end(__('Save')); ?>
