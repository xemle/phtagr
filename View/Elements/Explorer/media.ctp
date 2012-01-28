<h2><?php 
  if (!$this->Search->getUser() || $this->Search->getUser() != $this->Session->read('User.username')) {
    __("%s by %s", h($media['Media']['name']), $this->Html->link($media['User']['username'], "/explorer/user/".$media['User']['username']));
  } else {
    echo h($media['Media']['name']);
  }
?></h2>
<?php 
  $size = $this->ImageData->getimagesize($media, OUTPUT_SIZE_THUMB);
  $imageCrumbs = $this->Breadcrumb->replace($crumbs, 'page', $this->Search->getPage());
  $imageCrumbs = $this->Breadcrumb->replace($imageCrumbs, 'pos', ($pos + $index));
  if ($this->Search->getShow(12) != 12) {
    $imageCrumbs = $this->Breadcrumb->replace($imageCrumbs, 'show', $this->Search->getShow());
  }
  
  // image centrering from http://www.brunildo.org/test/img_center.html
  echo '<div class="p-explorer-media-image"><span></span>';
  echo $this->Html->tag('a',
    $this->Html->tag('img', false, array(
      'src' => Router::url("/media/thumb/".$media['Media']['id']),
      'width' => $size[0], 'height' => $size[1], 
      'alt' => $media['Media']['name'])),
    array('href' => Router::url("/images/view/".$media['Media']['id'].'/'.$this->Breadcrumb->params($imageCrumbs))));
  echo "</div>";
?>

<div class="p-explorer-media-actions" id="action-<?php echo $media['Media']['id']; ?>">
<?php if ($media['Media']['canWriteTag'] || $media['Media']['canReadOriginal']): ?>
<ul>
<?php
  if ($media['Media']['canWriteTag']) {
    $addIcon = $this->ImageData->getIcon('add',  __('Select media'));
    echo $this->Html->tag('li', 
      $this->Html->link($addIcon, 'javascript:void', array('escape' => false, 'class' => 'add')),
      array('escape' => false));
    $delIcon = $this->ImageData->getIcon('delete',  __('Deselect media'));
    echo $this->Html->tag('li', 
      $this->Html->link($delIcon, 'javascript:void', array('escape' => false, 'class' => 'del')),
      array('escape' => false));
    $editIcon = $this->ImageData->getIcon('tag',  __('Edit meta data'));
    echo $this->Html->tag('li', 
      $this->Html->link($editIcon, 'javascript:void', array('escape' => false, 'class' => 'edit')),
      array('escape' => false));
  }
  if ($this->Search->getUser() == $currentUser['User']['username'] && $media['Media']['canWriteAcl']) {
    $keyIcon = $this->Html->image('icons/key.png', array('title' => __('Edit access rights'), 'alt' => 'Edit ACL'));
    echo $this->Html->tag('li', 
      $this->Html->link($keyIcon, 'javascript:void', array('escape' => false, 'class' => 'acl')),
      array('escape' => false));
  }
  if ($media['Media']['canReadOriginal']) {
    foreach ($media['File'] as $file) {
      $diskIcon = $this->Html->image('icons/disk.png', array('title' => __('Download file %s', $file['file']), 'alt' => 'download'));
      echo $this->Html->tag('li', 
        $this->Html->link($diskIcon, Router::url('/media/file/' . $file['id'] . '/' . $file['file'], true), array('escape' => false, 'class' => 'download')),
        array('escape' => false));
    }
  }
?>
</ul>
<?php endif; ?>
</div>
<div class="p-explorer-media-description" id="<?php echo 'description-'.$media['Media']['id']; ?>">
<?php echo $this->element('explorer/description', array('media' => $media)); ?>
</div>
