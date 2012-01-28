<?php
  echo $imageData->acl2select('Media.readPreview', $this->data, ACL_READ_PREVIEW, ACL_READ_MASK, array('label' => __("Who can view image?", true)));
  echo $imageData->acl2select('Media.readOriginal', $this->data, ACL_READ_ORIGINAL, ACL_READ_MASK, array('label' => __("Who can download the image?", true)));
  echo $imageData->acl2select('Media.writeTag', $this->data, ACL_WRITE_TAG, ACL_WRITE_MASK, array('label' => __("Who can edit the tags?", true)));
  echo $imageData->acl2select('Media.writeMeta', $this->data, ACL_WRITE_META, ACL_WRITE_MASK, array('label' => __("Who can edit all meta data?", true)));
  echo $form->input('Group.id', array('type' => 'select', 'options' => $groups, 'selected' => $this->data['Media']['group_id'], 'label' => __('Group', true)));
?>
