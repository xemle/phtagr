<h1>Quick Search Results</h1>
<?php $session->flash(); ?>

<div class="minis">
<script type="text/javascript">
  var mediaData = [];
</script>

<?php
$search->initialize();
$cell=0;

if (count($dataTags) + count($dataCategories) + count($dataLocations) == 0): ?>
<div class="info">
Sorry, nothing was found for <?php echo $quicksearch; ?>
</div>
<?php endif; ?>

<?php // -- Output for Tags --
if (count($dataTags) > 0) : ?>
<h2>Results for Tags:</h2>
<div align="left"> 
<?php 
  foreach($dataTags as $media) {
    echo $imageData->mediaLink($media, 'mini').' ';
  }
?>
</div>

<?php
  echo 'See more results with tag: ';
  $names = Set::extract('/Tag/name', $dataTags);
  $names = array_unique($names);
  $links = array();
  foreach ($names as $name) {
    $links[] = $html->link($name, '/explorer/tag/'.$name);
  }
  echo implode(', ', $links);
?>
<?php endif; /* if (count($dataTags) > 0) */ ?>  

<?php // -- Output for Categories --
if (count($dataCategories) > 0) : ?>
<h2>Results for Categories:</h2>
<div align="left"> 
<?php 
  foreach($dataCategories as $media) {
    echo $imageData->mediaLink($media, 'mini').' ';
  }
?>
</div>

<?php
  echo 'See more results with category: ';
  $names = Set::extract('/Category/name', $dataCategories);
  $names = array_unique($names);
  $links = array();
  foreach ($names as $name) {
    $links[] = $html->link($name, '/explorer/category/'.$name);
  }
  echo implode(', ', $links);
?>
<?php endif; /* if (count($dataCategories) > 0) */ ?>  

<?php // -- Output for Locations --
if (count($dataLocations) > 0) : ?>
<h2>Results for Locations:</h2>
<div align="left"> 
<?php 
  foreach($dataLocations as $media) {
    echo $imageData->mediaLink($media, 'mini').' ';
  }
?>
</div>

<?php
  echo 'See more results with location: ';
  $names = Set::extract('/Location/name', $dataLocations);
  $names = array_unique($names);
  $links = array();
  foreach ($names as $name) {
    $links[] = $html->link($name, '/explorer/location/'.$name);
  }
  echo implode(', ', $links);
?>
<?php endif; /* if (count($dataLocations) > 0) */ ?>  

</div>
