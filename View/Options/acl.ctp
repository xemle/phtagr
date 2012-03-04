<h1><?php echo __('Access Rights'); ?></h1>
<?php echo $this->Session->flash(); ?>
<?php 
  $aclSelect = array(
    ACL_LEVEL_OTHER => __('Everyone'),
    ACL_LEVEL_USER => __('User'),
    ACL_LEVEL_GROUP => __('Group Members'),
    ACL_LEVEL_PRIVATE => __('Me only'));
?>
<?php echo $this->Form->create(null, array('action' => 'acl')); ?>
<fieldset><legend><?php echo __('Default Access Rights'); ?></legend>
<p><?php echo __('The following access rights are applied to new images.'); ?></p>
<?php
  echo $this->Form->input('acl.read.preview', array('type' => 'select', 'options' => $aclSelect, 'label' => __("Who can view the image?"))); 
  echo $this->Form->input('acl.read.original', array('type' => 'select', 'options' => $aclSelect, 'label' => __("Who can download the image?"))); 
  echo $this->Form->input('acl.write.tag', array('type' => 'select', 'options' => $aclSelect, 'label' => __("Who can add tags?"))); 
  echo $this->Form->input('acl.write.meta', array('type' => 'select', 'options' => $aclSelect, 'label' => __("Who can edit all meta data?"))); 
  echo $this->Form->input('acl.group', array('type' => 'select', 'options' => $groups, 'label' => __("Default image group?")));
?>
</fieldset>
<?php echo $this->Form->end(__('Save')); ?>
