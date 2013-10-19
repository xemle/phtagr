<h2><?php
  if (!$this->Search->getUser() || $this->Search->getUser() != $this->Session->read('User.username')) {
    echo __("%s by %s", h($media['Media']['name']), $this->Html->link($media['User']['username'], "/explorer/user/".$media['User']['username']));
  } else {
    echo h($media['Media']['name']);
  }
?></h2>
<?php
  $size = $this->ImageData->getimagesize($media, OUTPUT_SIZE_THUMB);
  $imageCrumbs = am($crumbs, array());
  if ($this->Search->getPage(1) != 1) {
    $imageCrumbs = $this->Breadcrumb->replace($imageCrumbs, 'page', $this->Search->getPage(1));
  }
  $imageCrumbs = $this->Breadcrumb->replace($imageCrumbs, 'pos', ($pos + $index));
  if ($this->Search->getShow(12) != 12) {
    $imageCrumbs = $this->Breadcrumb->replace($imageCrumbs, 'show', $this->Search->getShow(12));
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
<div class="description" id="<?php echo 'description-'.$media['Media']['id']; ?>">
<?php echo $this->element('Explorer/description', array('media' => $media)); ?>
</div>
