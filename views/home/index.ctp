<h1>Welcome to phTagr</h1>

<h2>Popular Tags</h2>
<?php 
  if (isset($cloudTags) && count($cloudTags)) {
    echo $cloud->cloud($cloudTags, '/explorer/tag/');
  } else {
    echo '<p>No tags assigned</p>';
  }
?>

<h2>Popular Categories</h2>
<?php 
  if (isset($cloudCategories) && count($cloudCategories)) {
    echo $cloud->cloud($cloudCategories, '/explorer/category/');
  } else {
    echo '<p>No categories assigned</p>';
  }
?>

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
  $img = '<img src="'.Router::url('/media/mini/'.$comment['Media']['id'].'/'.$comment['Media']['name']).'" />';
  $link = '/images/view/'.$comment['Media']['id'];
  echo '<div class="image">'.$html->link($img, $link, null, false, false).'</div>';?>
<?php echo $text->truncate(preg_replace('/\n/', '<br />', $comment['Comment']['text']), 220, '...', false, true); ?>
</div>
</div><!-- comment -->
<?php endforeach; /* comments */ ?>
</div><!-- comments -->
<div>
  <?php echo $html->link ("older comments...", "/comments", NULL, false, false);?>
</div>
<?php endif; ?>
