<h1>Quick Search Results</h1>
<?php $session->flash(); ?>


<?php
$search->initialize();
$cell=0;

if (!count($this->data)): ?>
<div class="info">
<?php printf(__("Sorry, nothing was found for %s", true), h($quicksearch)); ?>
</div>
<?php else: ?>

<h2><?php printf(__('Results of %s', true), h($quicksearch)); ?></h2>
<div class="minis" align="left">
<script type="text/javascript">
  var mediaData = [];
</script>

<?php 
  foreach($this->data as $media) {
    echo $imageData->mediaLink($media, 'mini').' ';
  }
?>
</div>

<?php
  $tags = Set::extract('/Tag/name', $this->data);
  if (count($tags)) {
    echo '<p>' . __('See more results of tag', true) .  ': ';
    $tags = array_unique($tags);
    $links = array();
    foreach ($tags as $name) {
      $links[] = $html->link($name, '/explorer/tag/'.$name);
    }
    echo implode(', ', $links) . '</p>';
  }

  $categories = Set::extract('/Category/name', $this->data);
  if (count($categories)) {
    echo '<p>' . __('See more results of category', true) .  ': ';
    $categories = array_unique($categories);
    $links = array();
    foreach ($categories as $name) {
      $links[] = $html->link($name, '/explorer/category/'.$name);
    }
    echo implode(', ', $links) . '</p>';
  }

  $locations = Set::extract('/Location/name', $this->data);
  if (count($locations)) {
    echo '<p>' . __('See more results of location', true) .  ': ';
    $locations = array_unique($locations);
    $links = array();
    foreach ($locations as $name) {
      $links[] = $html->link($name, '/explorer/location/'.$name);
    }
    echo implode(', ', $links) . '</p>';
  }
?>
<?php endif; ?>
