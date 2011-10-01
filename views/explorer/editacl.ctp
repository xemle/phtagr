<?php 
  $mediaId = $data['Media']['id'];
  echo $form->create(null, array('url' => 'saveacl/'.$mediaId, 'id' => 'form-acl-'.$mediaId));
?>
<fieldset>
<?php
  $aclSelect = array(
    ACL_LEVEL_PRIVATE => __('Me', true),
    ACL_LEVEL_GROUP => __('Group', true),
    ACL_LEVEL_USER => __('Users', true),
    ACL_LEVEL_OTHER => __('All', true));
  echo $html->tag('div',
    $html->tag('label', __("Who can view the image", true)).
    $html->tag('div', $form->radio('acl.read.preview', $aclSelect, array('legend' => false, 'value' => $data['Media']['acl']['read']['preview'])), array('escape' => false, 'class' => 'radioSet')), 
    array('escape' => false, 'class' => 'input radio'));
  echo $imageData->acl2select('acl.read.original', $data, ACL_READ_ORIGINAL, ACL_READ_MASK, array('label' => __("Who can download the image?", true)));
  echo $imageData->acl2select('acl.write.tag', $data, ACL_WRITE_TAG, ACL_WRITE_MASK, array('label' => __("Who can edit the tags?", true)));
  echo $imageData->acl2select('acl.write.meta', $data, ACL_WRITE_META, ACL_WRITE_MASK, array('label' => __("Who can edit all meta data?", true)));
  echo $form->input('Group.id', array('type' => 'select', 'options' => $groups, 'selected' => $data['Media']['group_id'], 'label' => __('Group', true)));
?>
</fieldset>
</form>
