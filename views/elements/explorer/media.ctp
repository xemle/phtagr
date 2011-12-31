<div class="p-explorer-media <?php echo ($media['Media']['canWriteTag'] ? 'editable' : '') . " cell" . ($index % 4); ?>" id="media-<?php echo $media['Media']['id']; ?>">
<h2><?php 
  if (!$search->getUser() || $search->getUser() != $session->read('User.username')) {
    printf(__("%s by %s", true), h($media['Media']['name']), $html->link($media['User']['username'], "/explorer/user/".$media['User']['username']));
  } else {
    echo h($media['Media']['name']);
  }
?></h2>
<?php 
  $size = $imageData->getimagesize($media, OUTPUT_SIZE_THUMB);
  $imageCrumbs = $this->Breadcrumb->replace($crumbs, 'page', $search->getPage());
  $imageCrumbs = $this->Breadcrumb->replace($imageCrumbs, 'pos', ($pos + $index));
  if ($search->getShow(12) != 12) {
    $imageCrumbs = $this->Breadcrumb->replace($imageCrumbs, 'show', $search->getShow());
  }
  
  // image centrering from http://www.brunildo.org/test/img_center.html
  echo '<div class="p-explorer-media-image"><span></span>';
  echo $html->tag('a',
    $html->tag('img', false, array(
      'src' => Router::url("/media/thumb/".$media['Media']['id']),
      'width' => $size[0], 'height' => $size[1], 
      'alt' => $media['Media']['name'])),
    array('href' => Router::url("/images/view/".$media['Media']['id'].'/'.$breadcrumb->params($imageCrumbs))));
  echo "</div>";
?>

<div class="p-explorer-media-actions" id="action-<?php echo $media['Media']['id']; ?>">
<?php if ($media['Media']['canWriteTag'] || $media['Media']['canReadOriginal']): ?>
<ul>
<?php
  if ($media['Media']['canWriteTag']) {
    $addIcon = $imageData->getIcon('add',  __('Select media', true));
    echo $html->tag('li', 
      $html->link($addIcon, 'javascript:void', array('escape' => false, 'class' => 'add')),
      array('escape' => false));
    $delIcon = $imageData->getIcon('delete',  __('Deselect media', true));
    echo $html->tag('li', 
      $html->link($delIcon, 'javascript:void', array('escape' => false, 'class' => 'del')),
      array('escape' => false));
    $editIcon = $imageData->getIcon('tag',  __('Edit meta data', true));
    echo $html->tag('li', 
      $html->link($editIcon, 'javascript:void', array('escape' => false, 'class' => 'edit')),
      array('escape' => false));
  }
  if ($search->getUser() == $currentUser['User']['username'] && $media['Media']['canWriteAcl']) {
    $keyIcon = $this->Html->image('icons/key.png', array('title' => __('Edit access rights', true), 'alt' => 'Edit ACL'));
    echo $html->tag('li', 
      $html->link($keyIcon, 'javascript:void', array('escape' => false, 'class' => 'acl')),
      array('escape' => false));
  }
  if ($media['Media']['canReadOriginal']) {
    foreach ($media['File'] as $file) {
      $diskIcon = $this->Html->image('icons/disk.png', array('title' => sprintf(__('Download file %s', true), $file['file']), 'alt' => 'download'));
      echo $html->tag('li', 
        $html->link($diskIcon, Router::url('/media/file/' . $file['id'] . '/' . $file['file'], true), array('escape' => false, 'class' => 'download')),
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
</div><!-- cell -->

