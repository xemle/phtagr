<h1>Preferences</h1>
<?php $session->flash(); ?>
<?php 
  $aclSelect = array(
    ACL_LEVEL_PUBLIC => 'Everyone',
    ACL_LEVEL_MEMBER => 'Members',
    ACL_LEVEL_GROUP => 'Guests',
    ACL_LEVEL_PRIVATE => 'Me only');
?>
<?php echo $form->create(null, array('action' => 'acl')); ?>
<fieldset><legend>Default Image Access Rights</legend>
<p>The following access rights are applied to new images.</p>
<?php
  echo $form->input('acl.read.preview', array('type' => 'select', 'options' => $aclSelect, 'label' => "Who can view the image?")); 
  echo $form->input('acl.read.download', array('type' => 'select', 'options' => $aclSelect, 'label' => "Who can download the image?")); 
  echo $form->input('acl.write.tag', array('type' => 'select', 'options' => $aclSelect, 'label' => "Who can add tags?")); 
  echo $form->input('acl.write.meta', array('type' => 'select', 'options' => $aclSelect, 'label' => "Who can edit all meta data?")); 
  echo $form->input('acl.write.comment', array('type' => 'select', 'options' => $aclSelect, 'label' => "Who can add comments?")); 
  echo $form->input('acl.group', array('type' => 'select', 'options' => $groups, 'label' => "Default image group?"));
?>
</fieldset>
<?php echo $form->end('Save'); ?>
<?php debug($this->data); ?>
<?php debug($groups); ?>
<?php if (isset($commit)) debug($commit); ?>
