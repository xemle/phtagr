<?php 
  $mediaId = $this->data['Media']['id'];
  echo $form->create(null, array('url' => 'saveacl/'.$mediaId, 'id' => 'form-acl-'.$mediaId));
?>
<fieldset>
<?php echo View::element('single_acl_form'); ?>
</fieldset>
</form>
