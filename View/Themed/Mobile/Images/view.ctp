<h1><?php echo $this->data['Media']['name'] ?></h1>
<?php echo $this->Session->flash(); ?>

<div class="paginator"><div class="subpaginator">
<?php
echo $this->Navigator->prevMedia().' '.$this->Navigator->up().' '.$this->Navigator->nextMedia();
?>
</div></div>

<?php
  if (($this->data['Media']['type'] & MEDIA_TYPE_VIDEO) > 0) {
    echo $this->Flowplayer->video($this->data);
  } else {
    $size = $this->ImageData->getimagesize($this->data, OUTPUT_SIZE_PREVIEW);
    echo $this->Html->tag('img', '', array(
      'src' => Router::url("/media/preview/" . $this->data['Media']['id']),
      'alt' => $this->data['Media']['name'],
      'style' => "width: 100%; max-width: {$size[0]}px")
      );
  }

  $media = $this->data;
  $names = Set::extract('/Tag/name', $media);
  $links = array();
  foreach($names as $name) {
    $links[] = $this->Html->link($name, "/explorer/tag/$name");
  }
  if (count($names)) {
    echo '<p>' . __("Tags") . ': ' . implode(', ', $links) . '</p>';
  }

  $names = Set::extract('/Category/name', $media);
  $links = array();
  foreach($names as $name) {
    $links[] = $this->Html->link($name, "/explorer/category/$name");
  }
  if (count($names)) {
    echo '<p>' . __("Categories") . ': ' . implode(', ', $links) . '</p>';
  }

  $names = Set::extract('/Location/name', $media);
  $links = array();
  foreach($names as $name) {
    $links[] = $this->Html->link($name, "/explorer/location/$name");
  }
  if (count($names)) {
    echo '<p>' . __("Location") . ': ' . implode(', ', $links) . '</p>';
  }
?>

<?php echo View::element('comment'); ?>
