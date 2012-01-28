<h1><?php printf(__('Group: %s', true), $this->data['Group']['name']); ?></h1>

<?php echo $session->flash() ?>

<?php echo $form->create(null, array('action' => "edit/{$this->data['Group']['name']}")); ?>

<fieldset><legend><?php __('Edit Group'); ?></legend>
<?php 
  echo $form->hidden('Group.id');
  echo $form->input('Group.name', array('label' => __('Name', true)));
  echo $form->input('Group.description', array('label' => __('Description', true), 'type' => 'blob'));
  echo $form->input('Group.is_hidden', array('label' => __('This group is hidden', true), 'type' => 'checkbox'));
  echo $form->input('Group.is_moderated', array('label' => __('New group members require a confirmation by the moderator', true), 'type' => 'checkbox'));
  echo $form->input('Group.is_shared', array('label' => __('The group can be used by other members', true), 'type' => 'checkbox'));
?>
</fieldset>
<?php echo $form->end(__('Save', true)); ?>
