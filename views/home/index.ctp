<h1>Welcome to phTagr</h1>

<h2>Popular Tags</h2>
<?php if ($cloudTags && count($cloudTags)): ?>
<?php 
$min = $cloudTags['_min'];
$max = $cloudTags['_max'];
$steps = 300/($max-$min+1);
foreach($cloudTags as $key => $tag) {
  if (is_numeric($key)) {
    //debug($tag);
    $name = $tag['Tag']['name'];
    $hits = $tag['Tag']['hits'];
    $size = 100+floor(($hits-$min)*$steps);
    echo "<span style=\"font-size: {$size}%\">";
    echo $html->link($name, "/explorer/tag/$name");
    echo "</span> ";
  }
}
?>
<?php else: ?>
<p>No tags found!</p>
<?php endif; ?>

<h2>Popular Categories</h2>
<?php if ($cloudCategories && count($cloudCategories)): ?>
<?php 
$min = $cloudCategories['_min'];
$max = $cloudCategories['_max'];
$steps = 300/($max-$min+1);
foreach($cloudCategories as $key => $tag) {
  if (is_numeric($key)) {
    //debug($tag);
    $name = $tag['Category']['name'];
    $hits = $tag['Category']['hits'];
    $size = 100+floor(($hits-$min)*$steps);
    echo "<span style=\"font-size: {$size}%\">";
    echo $html->link($name, "/explorer/category/$name");
    echo "</span> ";
  }
}
?>
<?php else: ?>
<p>No categories found!</p>
<?php endif; ?>
