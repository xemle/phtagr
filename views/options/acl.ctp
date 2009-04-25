<h1>Access Rights</h1>
<?php $session->flash(); ?>
<?php 
  $aclSelect = array(
    ACL_LEVEL_OTHER => 'Everyone',
    ACL_LEVEL_USER => 'User',
    ACL_LEVEL_GROUP => 'Guests',
    ACL_LEVEL_PRIVATE => 'Me only');
?>
<?php echo $form->create(null, array('action' => 'acl')); ?>
<fieldset><legend>Default Access Rights</legend>
<p>The following access rights are applied to new images.</p>
<?php
  echo $form->input('acl.read.preview', array('type' => 'select', 'options' => $aclSelect, 'label' => "Who can view the image?")); 
  echo $form->input('acl.read.original', array('type' => 'select', 'options' => $aclSelect, 'label' => "Who can download the image?")); 
  echo $form->input('acl.write.tag', array('type' => 'select', 'options' => $aclSelect, 'label' => "Who can add tags?")); 
  echo $form->input('acl.write.meta', array('type' => 'select', 'options' => $aclSelect, 'label' => "Who can edit all meta data?")); 
  echo $form->input('acl.group', array('type' => 'select', 'options' => $groups, 'label' => "Default image group?"));
?>
</fieldset>
<?php echo $form->end('Save'); ?>
