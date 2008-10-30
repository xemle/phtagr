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
    echo '<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key='.htmlentities($mapKey).'" type="text/javascript"></script>'."\n";
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

<?php if ($withMap): ?>
<div id="mapbox" style="display: none;">
<div id="map"></div>
<a href="#" onclick="toggleVisibility('mapbox')">Close Map</a>
</div>
<?php endif; /* withMap */ ?>

<?php echo View::element('comment'); ?>
