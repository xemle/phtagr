<h1><?php echo __(" User: %s", $this->data['User']['username']); ?></h1>

<?php echo $this->Session->flash(); ?>

<?php echo $this->Form->create(null, array('action' => 'password/'.$this->data['User']['id'])); ?>
<fieldset><legend>Password</legend>
<?php
  echo $this->Form->input('User.password', array('label' => __('Password')));
  echo $this->Form->input('User.confirm', array('label' => __('Confirm'), 'type' => 'password'));
?>
</fieldset>

<?php echo $this->Form->end(__('Save')); ?>
