<h1><?php echo __('New Group'); ?></h1>

<?php echo $this->Session->flash(); ?>

<?php echo $this->Form->create(null, array('url' => 'create')); ?>

<fieldset><legend><?php echo __('Create new group'); ?></legend>
<?php
  echo $this->Form->input('Group.name', array('label' => __('Name')));
  echo $this->Form->input('Group.description', array('label' => __('Description'), 'type' => 'textarea'));
  echo $this->Form->input('Group.is_hidden', array('label' => __('This group is hidden'), 'type' => 'checkbox'));
  echo $this->Form->input('Group.is_moderated', array('label' => __('New group members are moderated'), 'type' => 'checkbox'));
  echo $this->Form->input('Group.is_shared', array('label' => __('This group is shared and other group members can use this group'), 'type' => 'checkbox'));
?>
</fieldset>
<?php echo $this->Form->end(__('Create')); ?>
