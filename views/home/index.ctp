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

<?php if ($comments): ?>
<div class="comments">
<h2>Recent Comments</h2>
<?php $count = 0; ?>
<?php foreach ($comments as $comment): ?>
<div class="comment <?php echo ($count++%2)?'even':'odd'; ?>">
<div class="meta">
<span class="from"><?php echo $comment['Comment']['name'] ?></span> said 
<span class="date"><?php echo $time->relativeTime($comment['Comment']['date']); ?></span>
</div><!-- comment meta -->

<div class="text">
<?php 
  $img = '<img src="'.Router::url('/media/mini/'.$comment['Image']['id'].'/'.$comment['Image']['name']).'" />';
  $link = '/images/view/'.$comment['Image']['id'];
  echo '<div class="image">'.$html->link($img, $link, null, false, false).'</div>';?>
<?php echo $text->truncate(preg_replace('/\n/', '<br />', $comment['Comment']['text']), 220, '...', false, true); ?>
</div>
</div><!-- comment -->
<?php endforeach; /* comments */ ?>
</div><!-- comments -->
<?php endif; ?>
