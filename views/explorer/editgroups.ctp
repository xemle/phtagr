<?php 
  $mediaId = $data['Media']['id'];
  echo $ajax->form('savegroups/'.$mediaId, 'post', array('url' => '/explorer/savegroups/'.$mediaId, 'update' => 'meta-'.$mediaId)); 
?>
<fieldset>
<?php
  $groups = Set::extract('/Group[type>' . GROUP_TYPE_SYSTEM . ']/name', $data);
  echo $form->input('Group.names', array('type' => 'text', 'value' => implode(', ', $groups)));
?>
</fieldset>
<?php
  echo $form->submit('Save', array('div' => false)); 
  echo $ajax->link('Cancel', '/explorer/updatemeta/'.$mediaId, array('update' => 'meta-'.$mediaId, 'class' => 'reset'));
?>
</form>