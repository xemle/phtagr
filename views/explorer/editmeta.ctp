<?php 
  $mediaId = $this->data['Media']['id'];
  echo $form->create(null, array('url' => 'savemeta/'.$mediaId, 'id' => 'form-meta-'.$mediaId)); 
?>
<fieldset>
<?php
  echo View::element('single_meta_form');
?>
</fieldset>
</form>
