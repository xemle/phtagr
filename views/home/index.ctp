<div class="random-media">
<h3><?php __("Random Media"); ?></h3>
<?php 
  if (count($randomMedia)) {
    $media = $randomMedia[0];
    $params = '/'.$search->serialize(array('sort' => 'random'));

    $cite = "<cite>" . sprintf(__("%s by %s", true), h($media['Media']['name']), $html->link($media['User']['username'], '/explorer/user/' . $media['User']['username'])) . "</cite>";

    echo $html->tag('div', 
      $imageData->mediaLink($media, array('type' => 'preview', 'params' => $params)).$cite,
      array('class' => 'clip', 'escape' => false));

    $link = $search->getUri(array('sort' => 'random'));
    echo "<p>" . sprintf(__("See more %s", true), $html->link(__('random media...', true), $link))."</p>";
  } 
?>
</div>

<div class="newest-media">
<h3><?php __("Newest Media"); ?></h3>
<?php
  $links = array();
  $max = 6 * 4;
  $keys = array_keys($newMedia);
  foreach ($keys as $i) {
    if (count($links) >= $max) {
      continue;
    }
    $pos = $i + 1;
    $page = ceil($pos / $search->getShow(12));
    $params = '/'.$search->serialize(array('sort' => 'newest', 'page' => $page, 'pos' => $pos), false, false, array('defaults' => array('pos' => 1)));
    $links[] = $imageData->mediaLink($newMedia[$i], array('type' => 'mini', 'params' => $params));
  }
  echo $html->tag('div', implode("\n", $links), array('class' => 'images', 'escape' => false));
  $link = $search->getUri(array('sort' => 'newest'));
  echo "<p>" . sprintf(__("See %s", true), $html->link(__('all new media...', true), $link))."</p>";
?>
</div>

<div class="recent-comments">
<h3><?php __("Recent Comments"); ?></h3>
<?php if ($comments): ?>
<div class="comments">
<?php $count = 0; ?>
<?php foreach ($comments as $comment): ?>
<div class="comment <?php echo ($count++ % 2) ? 'even' : 'odd'; ?>">
<?php echo $imageData->mediaLink($comment, array('type' => 'mini', 'div' => 'image')); ?>
<div class="meta">
<span class="from"><?php echo $comment['Comment']['name'] ?></span> said 
<span class="date"><?php echo $time->relativeTime($comment['Comment']['date']); ?>:</span>
</div><!-- comment meta -->

<div class="text">
<?php echo preg_replace('/\n/', '<br />', $text->truncate($comment['Comment']['text'], 220, array('ending' => '...', 'exact' => false, 'html' => false))); ?>
</div>
</div><!-- comment -->
<?php endforeach; /* comments */ ?>
</div><!-- comments -->
<p><?php echo $html->link(__("Older comments...", true), "/comments", array('escape' => false));?></p>
<?php endif; ?>    
</div>

<div class="tag-cloud">
<h3><?php __("Popular Tags"); ?></h3>
<div class="cloud">
<?php
if (isset($cloudTags) && count($cloudTags)) {
  echo $cloud->cloud($cloudTags, '/explorer/tag/');
} else {
  echo '<p>' . __("No tags assigned") . '</p>';
}
?>
</div></div>

<div class="category-cloud">
<h3><?php __("Popular Categories"); ?></h3>
<div class="cloud">
<?php
if (isset($cloudCategories) && count($cloudCategories)) {
  echo $cloud->cloud($cloudCategories, '/explorer/category/');
} else {
  echo '<p>' . __("No categories assigned") . '</p>';
}
?>
</div></div>
