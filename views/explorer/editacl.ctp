<?php 
  $mediumId = $data['Medium']['id'];
  echo $ajax->form('saveacl/'.$mediumId, 'post', array('url' => '/explorer/saveacl/'.$mediumId, 'update' => 'meta-'.$mediumId)); 
?>
<fieldset>
<?php
  echo $form->input('Group.id', array('type' => 'select', 'options' => $groups, 'selected' => $data['Medium']['group_id'], 'label' => 'Group'));
  echo $imageData->acl2select('acl.write.tag', $data, ACL_WRITE_TAG, ACL_WRITE_MASK, array('label' => "Who can edit the tags?"));
  echo $imageData->acl2select('acl.write.meta', $data, ACL_WRITE_META, ACL_WRITE_MASK, array('label' => "Who can edit all meta data?"));
  echo $imageData->acl2select('acl.read.preview', $data, ACL_READ_PREVIEW, ACL_READ_MASK, array('label' => "Who can view image?"));
  echo $imageData->acl2select('acl.read.original', $data, ACL_READ_ORIGINAL, ACL_READ_MASK, array('label' => "Who can download the image?"));
?>
</fieldset>
<?php
  echo $form->submit('Save', array('div' => false)); 
  echo $ajax->link('Cancel', '/explorer/updatemeta/'.$mediumId, array('update' => 'meta-'.$mediumId, 'class' => 'reset'));
?>
</form>
