<div id="random-media">
<h3><?php echo __("Random Media"); ?></h3>
<?php
  if (count($randomMedia)) {
    $media = $randomMedia[0];
    $params = '/'.$this->Search->serialize(array('sort' => 'random'));

    $cite = "<cite>" . __("%s by %s", h($media['Media']['name']), $this->Html->link($media['User']['username'], '/explorer/user/' . $media['User']['username'])) . "</cite>";

    echo $this->Html->tag('div',
      $this->ImageData->mediaLink($media, array('type' => 'preview', 'params' => $params, 'width' => 470)).$cite,
      array('class' => 'clip', 'escape' => false));

    $link = $this->Search->getUri(array('sort' => 'random'));
    echo "<p>" . __("See more %s", $this->Html->link(__('random media...'), $link))."</p>";
  }
?>
</div>

<div id="newest-media">
<h3><?php echo __("Newest Media"); ?></h3>
<?php
  $links = array();
  $max = 6 * 4;
  $keys = array_keys($newMedia);
  foreach ($keys as $i) {
    if (count($links) >= $max) {
      continue;
    }
    $pos = $i + 1;
    $page = ceil($pos / $this->Search->getShow(12));
    $params = '/'.$this->Search->serialize(array('sort' => 'newest', 'page' => $page, 'pos' => $pos), false, false, array('defaults' => array('pos' => 1)));
    $links[] = $this->ImageData->mediaLink($newMedia[$i], array('type' => 'mini', 'params' => $params));
  }
  echo $this->Html->tag('div', implode("\n", $links), array('class' => 'images', 'escape' => false));
  $link = $this->Search->getUri(array('sort' => 'newest'));
  echo "<p>" . __("See %s", $this->Html->link(__('all new media...'), $link))."</p>";
?>
</div>

<div id="recent-comments">
<h3><?php echo __("Recent Comments"); ?></h3>
<?php if ($comments): ?>
<div class="comments">
<?php $count = 0; ?>
<?php foreach ($comments as $comment): ?>
<div class="comment <?php echo ($count++ % 2) ? 'even' : 'odd'; ?>">
<?php echo $this->ImageData->mediaLink($comment, array('type' => 'mini', 'div' => 'image')); ?>
<div class="meta">
<?php echo __('%s said %s:', '<span class="from">' . $comment['Comment']['name'] . '</span>', '<span class="date">' . $this->Time->timeAgoInWords($comment['Comment']['date']) . '</span>'); ?>
</div><!-- comment meta -->

<div class="text">
<?php echo preg_replace('/\n/', '<br />', $this->Text->truncate($comment['Comment']['text'], 220, array('ending' => '...', 'exact' => false, 'html' => false))); ?>
</div>
</div><!-- comment -->
<?php endforeach; /* comments */ ?>
</div><!-- comments -->
<p><?php echo $this->Html->link(__("Older comments..."), "/comments", array('escape' => false));?></p>
<?php endif; ?>
</div>

<div id="tag-cloud">
<h3><?php echo __("Popular Tags"); ?></h3>
<div class="cloud">
<?php
if (isset($cloudTags) && count($cloudTags)) {
  echo $this->Cloud->cloud($cloudTags, '/explorer/tag/');
} else {
  echo '<p>' . __("No tags assigned") . '</p>';
}
?>
</div>
<p><?php echo $this->Html->link(__("See more..."), 'cloud'); ?></p>
</div>

<div id="category-cloud">
<h3><?php echo __("Popular Categories"); ?></h3>
<div class="cloud">
<?php
if (isset($cloudCategories) && count($cloudCategories)) {
  echo $this->Cloud->cloud($cloudCategories, '/explorer/category/');
} else {
  echo '<p>' . __("No categories assigned") . '</p>';
}
?>
</div>
<p><?php echo $this->Html->link(__("See more..."), 'cloud'); ?></p>
</div>
