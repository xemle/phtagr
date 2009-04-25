<h1>User: <?=$this->data['User']['username']?></h1>

<?php $session->flash(); ?>

<?php echo $form->create(null, array('action' => 'edit/'.$this->data['User']['id'])); ?>
<fieldset><legend>General</legend>
<?php
  echo $form->input('User.firstname');
  echo $form->input('User.lastname');
  echo $form->input('User.email');
  $roles = array(ROLE_USER => 'User', ROLE_SYSOP => 'System Operator');
  if ($allowAdminRole) {
    $roles[ROLE_ADMIN] = 'Admin';
  }
  echo $form->input('User.role', array('type' => 'select', 'options' => $roles, 'selected' => $this->data['User']['role']));
?>
</fieldset>

<fieldset><legend>Password</legend>
<?php
  echo $form->input('User.password');
  echo $form->input('User.confirm', array('type' => 'password'));
?>
</fieldset>

<fieldset><legend>Other</legend>
<?php
  echo $form->input('User.expires', array('type' => 'text'));
  echo $form->input('User.quota', array('type' => 'text', 'label' => 'WebDAV Quota', 'value' => $number->toReadableSize($this->data['User']['quota'])));
?>
</fieldset>

<?php echo $form->submit('Save'); ?>
</form>
