<?php 
  $mediaId = $data['Media']['id'];
  echo $ajax->form('savemeta/'.$mediaId, 'post', array('url' => '/explorer/savemeta/'.$mediaId, 'update' => 'meta-'.$mediaId, 'id' => 'form-meta-'.$mediaId)); 
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
  echo $ajax->link('Cancel', '/explorer/updatemeta/'.$mediaId, array('update' => 'meta-'.$mediaId, 'class' => 'reset'));
?>
</form>
