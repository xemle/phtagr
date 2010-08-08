<h1><?php echo $this->data['Media']['name'] ?></h1>
<?php echo $session->flash(); ?>


<div class="paginator"><div class="subpaginator">
<?php
echo $navigator->prevMedia().' '.$navigator->up().' '.$navigator->nextMedia();
?>
</div></div>

<?php
  $withMap = false;
  if (isset($this->data['Media']['longitude']) && isset($this->data['Media']['latitude']) &&
    $map->hasApi()) {
    $withMap = true;
    echo $map->script();
  }
?>

<div class="mediaPreview">
<?php 
  if (($this->data['Media']['type'] & MEDIA_TYPE_VIDEO) > 0) {
    echo $flowplayer->video($this->data);
  } else {
    $size = $imageData->getimagesize($this->data, OUTPUT_SIZE_PREVIEW);
    echo "<img src=\"".Router::url("/media/preview/".$this->data['Media']['id'])."\" $size[3] alt=\"{$this->data['Media']['name']}\"/>"; 
  }
?>
</div>

<?php
  $items = array(array('name' => __("General", true), 'active' => true), __("Media Details", true));
  echo $tab->menu($items);
?>
<?php echo $tab->open(0, true); ?>
<div class="meta">
<div id="meta-<?php echo $this->data['Media']['id']; ?>">
<table class="bare"> 
  <?php echo $html->tableCells($imageData->metaTable(&$this->data, $withMap)); ?>
</table>
</div>
</div><!-- meta -->
<?php echo $tab->close(); ?>

<?php echo $tab->open(1); ?>
<div class="meta">
<table class="bare"> 
<?php 
  $cells = array();
  $cells[] = array(__("User", true), $html->link($this->data['User']['username'], '/explorer/user/'.$this->data['User']['username']));
  if ($this->data['Media']['isOwner']) {
    $files = array();
    foreach ($this->data['File'] as $file) {
      $link = $imageData->getPathLink($file);
      $files[] = $html->link($file['file'], $link).' ('.$number->toReadableSize($file['size']).')';
    }
    $cells[] = array(__("File(s)", true), implode(', ', $files));
  }
  $folders = $imageData->getFolderLinks($this->data);
  if ($folders) {
    $cells[] = array(__("Folder", true), implode(' / ', $folders));
  }
  $cells[] = array(__("View Count", true), $this->data['Media']['clicks']);
  $cells[] = array(__("Created", true), $time->relativeTime($this->data['Media']['created']));
  $cells[] = array(__("Last modified", true), $time->relativeTime($this->data['Media']['modified']));
  $cells[] = array(__("Size", true), $this->data['Media']['width'].'px * '.$this->data['Media']['height'].'px');

  if ($this->data['Media']['model']) {
    $cells[] = array(__("Model", true), $this->data['Media']['model']);
  }
  if ($this->data['Media']['duration'] > 0) {
    $cells[] = array(__("Duration", true), $this->data['Media']['duration'].'s');
  } else {
    if ($this->data['Media']['aperture'] > 0) {
      $cells[] = array(__("Aperture", true), $this->data['Media']['aperture']);
    }
    if ($this->data['Media']['shutter'] > 0) {
      $cells[] = array(__("Shutter", true), $imageData->niceShutter($this->data['Media']['shutter']));
    }
    if ($this->data['Media']['iso'] > 0) {
      $cells[] = array(__("ISO", true), $this->data['Media']['iso']);
    }
  }
  echo $html->tableCells($cells);
?>
</table>
</div>
<?php echo $tab->close(); ?>

<?php if ($withMap) {
  echo $map->container();
}
?>

<?php echo View::element('comment'); ?>
