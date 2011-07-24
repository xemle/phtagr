<?php 
  $mediaId = $data['Media']['id'];
  echo $ajax->form('savemeta/'.$mediaId, 'post', array('url' => '/explorer/savemeta/'.$mediaId, 'update' => 'meta-'.$mediaId, 'id' => 'form-meta-'.$mediaId)); 
?>
<fieldset>
<?php
  echo $form->input('Tags.text', array('label' => __('Tags', true), 'value' => join(', ', Set::extract('/Tag/name', $data))));
  echo $autocomplete->autoComplete('Tags.text', 'autocomplete/tag', array('split' => true));
?>
</fieldset>
</form>
