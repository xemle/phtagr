<?php
  $size = $this->ImageData->getimagesize($media, 104);
  $imageCrumbs = $this->Breadcrumb->replace($crumbs, 'page', $this->Search->getPage());
  $imageCrumbs = $this->Breadcrumb->replace($imageCrumbs, 'pos', ($pos + $index));
  if ($this->Search->getShow(12) != 12) {
    $imageCrumbs = $this->Breadcrumb->replace($imageCrumbs, 'show', $this->Search->getShow());
  }

  // image centrering from http://www.brunildo.org/test/img_center.html
  echo '<div class="preview"><span></span>';
  echo $this->Html->tag('a',
    $this->Html->tag('img', false, array(
      'src' => Router::url("/media/thumb/".$media['Media']['id']),
      'width' => $size[0], 'height' => $size[1],
      'alt' => $media['Media']['name'])),
    array('href' => Router::url("/images/view/".$media['Media']['id'].'/'.$this->Breadcrumb->params($imageCrumbs))));
  echo "</div>";
?>

<div class="actions" id="action-<?php echo $media['Media']['id']; ?>">
  <?php echo $this->element('Explorer/actions', array('media' => $media)); ?>
</div>