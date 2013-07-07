<h1><?php echo __('Add new User'); ?></h1>

<?php echo $this->Session->flash(); ?>

<?php echo $this->Form->create('User', array('url' => 'add')); ?>
<fieldset><legend><?php echo __('Create new user'); ?></legend>
<?php
  echo $this->Form->input('User.username', array('label' => __('Username')));
  echo $this->Form->input('User.email', array('label' => __('Email')));
  echo $this->Form->input('User.password', array('label' => __('Password')));
  echo $this->Form->input('User.confirm', array('label' => __('Confirm'), 'type' => 'password'));
?>
</fieldset>
<?php echo $this->Form->end(__("Create")); ?>
