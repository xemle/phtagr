<div class="p-explorer-cell <?php echo ($media['Media']['canWriteTag'] ? 'editable' : '') . " cell" . ($index % 4); ?>" id="media-<?php echo $media['Media']['id']; ?>">
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
  $imageCrumbs = $this->Breadcrumb->replace($imageCrumbs, 'pos', $pos++);
  if ($search->getShow(12) != 12) {
    $imageCrumbs = $this->Breadcrumb->replace($imageCrumbs, 'show', $search->getShow());
  }
  
  // image centrering from http://www.brunildo.org/test/img_center.html
  echo '<div class="p-explorer-cell-image"><span></span>';
  echo $html->tag('a',
    $html->tag('img', false, array(
      'src' => Router::url("/media/thumb/".$media['Media']['id']),
      'width' => $size[0], 'height' => $size[1], 
      'alt' => $media['Media']['name'])),
    array('href' => Router::url("/images/view/".$media['Media']['id'].'/'.$breadcrumb->params($imageCrumbs))));
  echo "</div>";
?>

<div class="p-explorer-cell-actions" id="action-<?php echo $media['Media']['id']; ?>">
<?php if ($media['Media']['canWriteTag'] || $media['Media']['canReadOriginal']): ?>
<ul>
<?php
  if ($media['Media']['canWriteTag']) {
    $addIcon = $this->Html->image('icons/add.png', array('title' => __('Add to the selection', true), 'alt' => 'add'));
    echo $html->tag('li', 
      $html->link($addIcon, 'javascript:void', array('escape' => false, 'class' => 'add')),
      array('escape' => false));
    $delIcon = $this->Html->image('icons/delete.png', array('title' => __('Remove from the selection', true), 'alt' => 'remove'));
    echo $html->tag('li', 
      $html->link($delIcon, 'javascript:void', array('escape' => false, 'class' => 'del')),
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

<div class="p-explorer-cell-meta" id="<?php echo 'meta-'.$media['Media']['id']; ?>">
<?php echo $this->element('explorer/date', array('media' => $media)); ?>
<?php if (count($media['Tag'])): ?>
  <dd class="tag list"><?php echo __("Tags", true); ?></dd>
  <dt><?php echo implode(', ', $imageData->linkList('/explorer/tag', Set::extract('/Tag/name', $media))); ?></dt>
<?php endif; ?>
<?php if (count($media['Category'])): ?>
  <dd class="category list"><?php echo __("Categories", true); ?></dd>
  <dt><?php echo implode(', ', $imageData->linkList('/explorer/category', Set::extract('/Category/name', $media))); ?></dt>
<?php endif; ?>
<?php if (count($media['Location'])): ?>
  <dd class="location list"><?php echo __("Locations", true); ?></dd>
  <dt><?php echo implode(', ', $imageData->linkList('/explorer/location', Set::extract('/Location/name', $media))); ?></dt>
<?php endif; ?>
</dl>
</div><!-- meta -->
</div><!-- cell -->

