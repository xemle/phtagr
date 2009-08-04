<?php $session->flash(); ?>

<?php echo $form->create('User', array('action' => 'register')); ?>
<fieldset>
<legend>Create Account</legend>
<?php
  echo $form->input('User.username');
  echo $form->input('User.password');
  echo $form->input('User.confirm', array('type' => 'password'));
  echo $form->input('User.email');
  echo '<div class="input text"><label>&nbsp;</label><img src="'.$html->url('/users/captcha/verify.jpg').'" /></div>';
  echo $form->input('Captcha.verification');
?>
</fieldset>
<?php echo $form->submit('Create'); ?>
</form>

