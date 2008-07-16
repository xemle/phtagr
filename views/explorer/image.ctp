<?php if (!isset($data)): ?>
<div class="info">No Image could be found</div>
<?php else: ?>
<h1><?php echo $data['Image']['name'] ?></h1>
<?php $session->flash(); ?>

<div class="paginator"><div class="subpaginator">
<?php
echo $query->prevImage().' '.$query->up().' '.$query->nextImage();
?>
</div></div>

<?php
  $withMap = false;
  if (isset($data['Image']['longitude']) && isset($data['Image']['latitude']) &&
    isset($mapKey)) {
    $withMap = true;
    echo '<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key='.htmlentities($mapKey).'" type="text/javascript"></script>'."\n";
  }
?>

<?php 
  $size = $imageData->getimagesize($data, OUTPUT_SIZE_PREVIEW);
  echo "<img src=\"".Router::url("/files/preview/".$data['Image']['id'])."\" $size[3] alt=\"{$data['Image']['name']}\"/>"; ?>

<div class="meta">
<div id="<?php echo 'meta-'.$data['Image']['id']; ?>">
<table> 
  <?php echo $html->tableCells($imageData->metaTable(&$data, $withMap)); ?>
</table>
</div>
</div><!-- meta -->

<?php if ($withMap): ?>
<div id="mapbox" style="display: none;">
<div id="map"></div>
<a href="#" onclick="toggleVisibility('mapbox')">Close Map</a>
</div>
<?php endif; ?>

<?php endif; ?>
