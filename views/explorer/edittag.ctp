<?php 
  $mediaId = $data['Media']['id'];
  echo $ajax->form('savemeta/'.$mediaId, 'post', array('url' => '/explorer/savemeta/'.$mediaId, 'update' => 'meta-'.$mediaId, 'id' => 'form-meta-'.$mediaId)); 
?>
<fieldset>
<?php
  echo $html->tag('div',
    $form->label('Tags.text', __('Tags', true)).
    $ajax->autoComplete('Tags.text', 'autocomplete/tag', array('value' => implode(', ', Set::extract('/Tag/name', $data)), 'tokens' => ',')), 
    array('class' => 'input text'));
?>
</fieldset>
<?php
  echo $form->submit('Save', array('div' => false)); 
  echo $ajax->link('Cancel', '/explorer/updatemeta/'.$mediaId, array('update' => 'meta-'.$mediaId, 'class' => 'reset'));
?>
</form>
