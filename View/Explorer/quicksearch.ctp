<h1>Quick Search Results</h1>
<?php echo $this->Session->flash(); ?>


<?php
$this->Search->initialize();
$cell=0;

if (!count($this->request->data)): ?>
<div class="info">
<?php echo __("Sorry, nothing was found for %s", h($quicksearch)); ?>
</div>
<?php else: ?>

<h2><?php echo __('Results of %s', h($quicksearch)); ?></h2>
<div class="minis" align="left">
<script type="text/javascript">
  var mediaData = [];
</script>

<?php 
  foreach($this->request->data as $media) {
    echo $this->ImageData->mediaLink($media, 'mini').' ';
  }
?>
</div>

<?php
  $tags = Set::extract('/Tag/name', $this->request->data);
  if (count($tags)) {
    echo '<p>' . __('See more results of tag') .  ': ';
    $tags = array_unique($tags);
    $links = array();
    foreach ($tags as $name) {
      $links[] = $this->Html->link($name, '/explorer/tag/'.$name);
    }
    echo implode(', ', $links) . '</p>';
  }

  $categories = Set::extract('/Category/name', $this->request->data);
  if (count($categories)) {
    echo '<p>' . __('See more results of category') .  ': ';
    $categories = array_unique($categories);
    $links = array();
    foreach ($categories as $name) {
      $links[] = $this->Html->link($name, '/explorer/category/'.$name);
    }
    echo implode(', ', $links) . '</p>';
  }

  $locations = Set::extract('/Location/name', $this->request->data);
  if (count($locations)) {
    echo '<p>' . __('See more results of location') .  ': ';
    $locations = array_unique($locations);
    $links = array();
    foreach ($locations as $name) {
      $links[] = $this->Html->link($name, '/explorer/location/'.$name);
    }
    echo implode(', ', $links) . '</p>';
  }
?>
<?php endif; ?>
