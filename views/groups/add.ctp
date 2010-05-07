<h1><?php __('New Group'); ?></h1>

<?php $session->flash(); ?>

<?php echo $form->create(null, array('action' => 'add')); ?>

<fieldset><legend><?php __('Create new group'); ?></legend>
<?php
  echo $form->input('Group.name', array('label' => __('Name', true)));
?>
</fieldset>

<?php echo $form->end(__('Create', true)); ?>
