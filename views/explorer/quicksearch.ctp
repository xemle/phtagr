<h1>Quick Search Results</h1>
<?php $session->flash(); ?>

<div class="minis">
<script type="text/javascript">
  var mediaData = [];
</script>

<?php
$query->initialize();
$cell=0;
$pos = ($query->get('page', 1)-1) * $query->get('show', 12) + 1;

if (count($dataTags) + count($dataCategories) + count($dataLocations) == 0): ?>
<div class="info">
Sorry, nothing was found for <?php echo $quicksearch; ?>
</div>
<?php endif; ?>

<?php // -- Output for Tags --
if (count($dataTags) > 0) : ?>
<h2>Results for Tags:</h2>
<div align="left"> 

<?php foreach($dataTags as $media): ?>

<?php  
	$size = $imageData->getimagesize($media, OUTPUT_SIZE_MINI); 
  $query->set('pos', $pos++); 
  echo "<a href=\"".Router::url("/images/view/".$media['Media']['id'].'/'.$query->getParams())."\">"; 
  echo "<img src=\"".Router::url("/media/mini/".$media['Media']['id'])."\" $size[3] alt=\"".$media['Media']['name']."\"/>";  
  echo "</a>"; 
?> 
<?php $cell++; endforeach; ?>
</div>

<?php
	echo 'See more results with tag ';
	foreach ($quicksearch as $s) {
		echo $html->link($s,'/explorer/tag/'.$s).' ';
}
?>
<?php endif; /* if (count($dataTags) > 0) */ ?>  

<?php // -- Output for Categories --
if (count($dataCategories) > 0) : ?>
<h2>Results for Categories:</h2>
<div align="left"> 

<?php foreach($dataCategories as $media): ?>

<?php  
	$size = $imageData->getimagesize($media, OUTPUT_SIZE_MINI); 
  $query->set('pos', $pos++); 
  echo "<a href=\"".Router::url("/images/view/".$media['Media']['id'].'/'.$query->getParams())."\">"; 
  echo "<img src=\"".Router::url("/media/mini/".$media['Media']['id'])."\" $size[3] alt=\"".$media['Media']['name']."\"/>";  
  echo "</a>"; 
?> 
<?php $cell++; endforeach; ?>
</div>

<?php
	echo 'See more results with Category ';
	foreach ($quicksearch as $s) {
		echo $html->link($s,'/explorer/category/'.$s).' ';
}
?>
<?php endif; /* if (count($dataCategories) > 0) */ ?>  

<?php // -- Output for Locations --
if (count($dataLocations) > 0) : ?>
<h2>Results for Locations:</h2>
<div align="left"> 

<?php foreach($dataLocations as $media): ?>

<?php  
	$size = $imageData->getimagesize($media, OUTPUT_SIZE_MINI); 
  $query->set('pos', $pos++); 
  echo "<a href=\"".Router::url("/images/view/".$media['Media']['id'].'/'.$query->getParams())."\">"; 
  echo "<img src=\"".Router::url("/media/mini/".$media['Media']['id'])."\" $size[3] alt=\"".$media['Media']['name']."\"/>";  
  echo "</a>"; 
?> 
<?php $cell++; endforeach; ?>
</div>

<?php
	echo 'See more results with Location ';
	foreach ($quicksearch as $s) {
		echo $html->link($s,'/explorer/location/'.$s).' ';
}
?>
<?php endif; /* if (count($dataLocations) > 0) */ ?>  

</div>
