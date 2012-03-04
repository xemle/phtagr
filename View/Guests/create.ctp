<h1><?php echo __('Guest Creation'); ?></h1>

<?php echo $this->Session->flash(); ?>

<?php echo $this->Form->create('Guest', array('action' => 'create')); ?>

<fieldset><legend><?php echo __('Create new guest account'); ?></legend>
<?php
  echo $this->Form->input('Guest.username', array('label' => __('Username')));
  echo $this->Form->input('Guest.email', array('label' => __('Email')));
  echo $this->Form->input('Guest.password', array('label' => __('Password')));
  echo $this->Form->input('Guest.confirm', array('type' => 'password', 'label' => __('Confirm')));
?>
</fieldset>
<?php echo $this->Form->end(__('Create')); ?>
