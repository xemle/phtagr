<?php $session->flash(); ?>
<h1>Create Database Configuration</h1>

<p>This step creates the configuration for the database connection. Please add your database credentials here.</p>

<?php echo $form->create(null, array('action' => 'config')); ?>

<fieldset><legend>Database</legend>
<?php 
  echo $form->input('db.host', array('label' => 'Host'));
  echo $form->input('db.database', array('label' => 'Database'));
  echo $form->input('db.login', array('label' => 'Username'));
  echo $form->input('db.password', array('label' => 'Password', 'type' => 'password'));
  echo $form->input('db.prefix', array('label' => 'Prefix'));
?>
</fieldset>

<?php echo $form->submit('Save'); ?>
</form>
