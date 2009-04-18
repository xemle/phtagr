<?php $session->flash(); ?>

<h1>Create Admin Account</h1>

<?php echo $form->create(null, array('action' => 'user')); ?>

<fieldset>
<?php echo $form->input('User.username'); ?>
<?php echo $form->input('User.password'); ?>
</fieldset>
<?php echo $form->submit('Create'); ?>

</form>

