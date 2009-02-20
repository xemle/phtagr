<?php 
  $mediumId = $data['Medium']['id'];
  echo $ajax->form('savemeta/'.$mediumId, 'post', array('url' => '/explorer/savemeta/'.$mediumId, 'update' => 'meta-'.$mediumId, 'id' => 'form-meta-'.$mediumId)); 
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
  echo $ajax->link('Cancel', '/explorer/updatemeta/'.$mediumId, array('update' => 'meta-'.$mediumId, 'class' => 'reset'));
?>
</form>
