<h1><?php echo $this->data['Image']['name'] ?></h1>
<?php $session->flash(); ?>


<div class="paginator"><div class="subpaginator">
<?php
echo $query->prevImage().' '.$query->up().' '.$query->nextImage();
?>
</div></div>

<?php
  $withMap = false;
  if (isset($this->data['Image']['longitude']) && isset($this->data['Image']['latitude']) &&
    isset($mapKey)) {
    $withMap = true;
    echo $map->loadScripts($mapKey);
    echo $map->script();
  }
?>

<?php 
  $size = $imageData->getimagesize($this->data, OUTPUT_SIZE_PREVIEW);
  echo "<img src=\"".Router::url("/media/preview/".$this->data['Image']['id'])."\" $size[3] alt=\"{$this->data['Image']['name']}\"/>"; ?>

<div class="meta">
<div id="<?php echo 'meta-'.$this->data['Image']['id']; ?>">
<table> 
  <?php echo $html->tableCells($imageData->metaTable(&$this->data, $withMap)); ?>
</table>
</div>
</div><!-- meta -->

<?php if ($withMap) {
  echo $map->container();
}
?>

<?php echo View::element('comment'); ?>
