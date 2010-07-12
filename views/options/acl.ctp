<h1><?php __('Access Rights'); ?></h1>
<?php echo $session->flash(); ?>
<?php 
  $aclSelect = array(
    ACL_LEVEL_OTHER => __('Everyone', true),
    ACL_LEVEL_USER => __('User', true),
    ACL_LEVEL_GROUP => __('Guests', true),
    ACL_LEVEL_PRIVATE => __('Me only', true));
?>
<?php echo $form->create(null, array('action' => 'acl')); ?>
<fieldset><legend><?php __('Default Access Rights'); ?></legend>
<p><?php __('The following access rights are applied to new images.'); ?></p>
<?php
  echo $form->input('acl.read.preview', array('type' => 'select', 'options' => $aclSelect, 'label' => __("Who can view the image?", true))); 
  echo $form->input('acl.read.original', array('type' => 'select', 'options' => $aclSelect, 'label' => __("Who can download the image?", true))); 
  echo $form->input('acl.write.tag', array('type' => 'select', 'options' => $aclSelect, 'label' => __("Who can add tags?", true))); 
  echo $form->input('acl.write.meta', array('type' => 'select', 'options' => $aclSelect, 'label' => __("Who can edit all meta data?", true))); 
  echo $form->input('acl.group', array('type' => 'select', 'options' => $groups, 'label' => __("Default image group?", true)));
?>
</fieldset>
<?php echo $form->end(__('Save', true)); ?>
