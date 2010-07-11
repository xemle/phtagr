<h1><?php __("Create admin account"); ?></h1>

<?php echo $session->flash(); ?>

<?php echo $form->create(null, array('action' => 'user')); ?>

<fieldset>
<?php 
  echo $form->input('User.username', array('label' => __("Username", true))); 
  echo $form->input('User.password', array('label' => __("Password", true)));
  echo $form->input('User.confirm', array('label' => __("Confirm", true), 'type' => 'password'));
  echo $form->input('User.email', array('label' => __("Email", true)));
?>
</fieldset>
<?php echo $form->submit(__('Create', true)); ?>

</form>

