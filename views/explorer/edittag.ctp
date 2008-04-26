<?php 
  $imageId = $data['Image']['id'];
  echo $ajax->form('savemeta/'.$imageId, 'post', array('url' => '/explorer/savemeta/'.$imageId, 'update' => 'meta-'.$imageId, 'id' => 'form-meta-'.$imageId)); 
?>
<fieldset>
<?php
  $tags = Set::extract($data, "Tag.{n}.name");
  $tagText = implode(', ', $tags);
  echo $form->input('Tags.text', array('value' => $tagText, 'label' => 'Tags'));
?>
</fieldset>
<?php
  echo $form->submit('Save', array('div' => false)); 
  echo $ajax->link('Cancel', '/explorer/updatemeta/'.$imageId, array('update' => 'meta-'.$imageId, 'class' => 'reset'));
?>
</form>
