<?php 
  $mediaId = $data['Media']['id'];
  echo $form->create(null, array('url' => 'saveacl/'.$mediaId, 'id' => 'form-acl-'.$mediaId));
?>
<fieldset>
<?php
  echo $imageData->acl2select('acl.read.preview', $data, ACL_READ_PREVIEW, ACL_READ_MASK, array('label' => __("Who can view image?", true)));
  echo $imageData->acl2select('acl.read.original', $data, ACL_READ_ORIGINAL, ACL_READ_MASK, array('label' => __("Who can download the image?", true)));
  echo $imageData->acl2select('acl.write.tag', $data, ACL_WRITE_TAG, ACL_WRITE_MASK, array('label' => __("Who can edit the tags?", true)));
  echo $imageData->acl2select('acl.write.meta', $data, ACL_WRITE_META, ACL_WRITE_MASK, array('label' => __("Who can edit all meta data?", true)));
  echo $form->input('Group.id', array('type' => 'select', 'options' => $groups, 'selected' => $data['Media']['group_id'], 'label' => __('Group', true)));
?>
</fieldset>
</form>
