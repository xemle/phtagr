<h1><?php echo $this->data['Media']['name'] ?></h1>
<?php echo $session->flash(); ?>

<div class="paginator"><div class="subpaginator">
<?php
echo $navigator->prevMedia().' '.$navigator->up().' '.$navigator->nextMedia();
?>
</div></div>

<?php 
  if (($this->data['Media']['type'] & MEDIA_TYPE_VIDEO) > 0) {
    echo $flowplayer->video($this->data);
  } else {
    $size = $imageData->getimagesize($this->data, OUTPUT_SIZE_PREVIEW);
    echo $html->tag('img', '', array(
      'src' => Router::url("/media/preview/" . $this->data['Media']['id']),
      'alt' => $this->data['Media']['name'],
      'style' => "width: 100%; max-width: {$size[0]}px")
      );  
  }

  $media = $this->data;
  $names = Set::extract('/Tag/name', $media);
  $links = array();
  foreach($names as $name) {
    $links[] = $html->link($name, "/explorer/tag/$name");
  }
  if (count($names)) {
    echo '<p>' . __("Tags", true) . ': ' . implode(', ', $links) . '</p>';
  }

  $names = Set::extract('/Category/name', $media);
  $links = array();
  foreach($names as $name) {
    $links[] = $html->link($name, "/explorer/category/$name");
  }
  if (count($names)) {
    echo '<p>' . __("Categories", true) . ': ' . implode(', ', $links) . '</p>';
  }

  $names = Set::extract('/Location/name', $media);
  $links = array();
  foreach($names as $name) {
    $links[] = $html->link($name, "/explorer/location/$name");
  }
  if (count($names)) {
    echo '<p>' . __("Location", true) . ': ' . implode(', ', $links) . '</p>';
  }
?>

<?php echo View::element('comment'); ?>
