<h1><?php __('New Group'); ?></h1>

<?php echo $session->flash(); ?>

<?php echo $form->create(null, array('action' => 'create')); ?>

<fieldset><legend><?php __('Create new group'); ?></legend>
<?php
  echo $form->input('Group.name', array('label' => __('Name', true)));
  echo $form->input('Group.description', array('label' => __('Description', true), 'type' => 'blob'));
  echo $form->input('Group.is_hidden', array('label' => __('This group is hidden', true), 'type' => 'checkbox'));
  echo $form->input('Group.is_moderated', array('label' => __('New group members are moderated', true), 'type' => 'checkbox'));
  echo $form->input('Group.is_shared', array('label' => __('This group is shared and other group members can use this group', true), 'type' => 'checkbox'));
?>
</fieldset>
<?php echo $form->end(__('Create', true)); ?>
