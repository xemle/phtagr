<ul>
<?php
  $addIcon = $this->ImageData->getIcon('add',  __('Select media'));
  echo $this->Html->tag('li',
    $this->Html->link($addIcon, 'javascript:void', array('escape' => false, 'class' => 'add')),
    array('escape' => false));
  $delIcon = $this->ImageData->getIcon('delete',  __('Deselect media'));
  echo $this->Html->tag('li',
    $this->Html->link($delIcon, 'javascript:void', array('escape' => false, 'class' => 'del')),
    array('escape' => false));
  if ($media['Media']['canWriteTag']) {
    $editIcon = $this->ImageData->getIcon('pencil',  __('Edit meta data'));
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