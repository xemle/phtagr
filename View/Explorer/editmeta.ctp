<?php
  $mediaId = $this->request->data['Media']['id'];
  $crumbUrl = '/'.$this->Breadcrumb->params($crumbs);
  echo $this->Form->create(null, array('url' => 'savemeta/'.$mediaId.$crumbUrl, 'id' => 'form-meta-'.$mediaId));
?>
<fieldset>
<?php
  echo View::element('single_meta_form');
?>
</fieldset>
</form>
