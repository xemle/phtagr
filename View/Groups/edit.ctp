<h1><?php echo __('Group: %s', $this->request->data['Group']['name']); ?></h1>

<?php echo $this->Session->flash() ?>

<?php echo $this->Form->create(null, array('url' => "edit/{$this->request->data['Group']['name']}")); ?>

<fieldset><legend><?php echo __('Edit Group'); ?></legend>
<?php
  echo $this->Form->hidden('Group.id');
  echo $this->Form->input('Group.name', array('label' => __('Name')));
  echo $this->Form->input('Group.description', array('label' => __('Description'), 'type' => 'textarea'));
  echo $this->Form->input('Group.is_hidden', array('label' => __('This group is hidden'), 'type' => 'checkbox'));
  echo $this->Form->input('Group.is_moderated', array('label' => __('New group members require a confirmation by the moderator'), 'type' => 'checkbox'));
  echo $this->Form->input('Group.is_shared', array('label' => __('The group can be used by other members'), 'type' => 'checkbox'));
?>
</fieldset>
<?php echo $this->Form->end(__('Save')); ?>
