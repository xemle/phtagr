<?php $session->flash(); ?>

<?php echo $form->create(null, array('action' => 'add')); ?>

<fieldset><legend>Create new group</legend>
<table class="formular">
<?php
  echo $form->input('Group.name');
?>
</table>

</fieldset>
<?php echo $form->submit('Create'); ?>
</form>
