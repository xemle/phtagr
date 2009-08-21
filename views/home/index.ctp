<h1>Welcome to phTagr</h1>

<div class="subcolumns">
  <div class="c50l">
    <div class="subcl">
      <h3>Random Media</h3>
      <?php 
        if (isset($randomMedia)) {
          $params = '/'.$search->serialize(array('sort' => 'random'));
          echo $imageData->mediaLink($randomMedia[0], array('type' => 'preview', 'size' => 340, 'div' => 'image', 'params' => $params));
          $link = $search->getUri(array('sort' => 'random'));
          echo "<p>See more ".$html->link('random media...', $link)."</p>";
        } 
      ?>
    </div>
  </div>

  <div class="c50r">
    <div class="subcr">
      <h3>Newest Media</h3>
      <?php
        $cells = array();
        $i = 0;
        $keys = array_keys($newMedia);
        for ($c = 0; $c < 3; $c++) {
          $row = array();
          for ($r = 0; $r < 4; $r++) {
            if (!isset($keys[$i])) {
              continue;
            }
    
            $params = '/'.$search->serialize(array('sort' => 'newest', 'pos' => $i + 1), false, false, array('defaults' => array('pos' => 1)));
            $row[] = $imageData->mediaLink($newMedia[$keys[$i++]], array('type' => 'mini', 'div' => 'image', 'params' => $params));
          }
          $cells[] = $row;
        }
      ?> 
      <table class="bare">
        <tbody>
          <?php echo $html->tableCells($cells); ?>
        </tbody>
      </table>
      <?php
        $link = $search->getUri(array('sort' => 'newest'));
        echo "<p>See ".$html->link('all new media...', $link)."</p>";
      ?>
    </div>
  </div>
</div><!-- subcolumns -->

<div class="subcolumns">
  <div class="c50l">
    <div class="subcl">
    <h3>Recent Comments</h3>
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
      <?php echo $text->truncate(preg_replace('/\n/', '<br />', $comment['Comment']['text']), 220, '...', false, true); ?>
      </div>
      </div><!-- comment -->
      <?php endforeach; /* comments */ ?>
      </div><!-- comments -->
      <div>
        <?php echo $html->link ("older comments...", "/comments", NULL, false, false);?>
      </div>
      <?php endif; ?>    
    </div>
  </div>

  <div class="c50r">
    <div class="subcr">
      <h3>Popular Tags</h3>
        <?php
        if (isset($cloudTags) && count($cloudTags)) {
          echo $cloud->cloud($cloudTags, '/explorer/tag/');
        } else {
          echo '<p>No tags assigned</p>';
        }
        ?>
      <h3>Popular Categories</h3>
        <?php
        if (isset($cloudCategories) && count($cloudCategories)) {
          echo $cloud->cloud($cloudCategories, '/explorer/category/');
        } else {
          echo '<p>No categories assigned</p>';
        }
        ?>
     </div>
  </div>
</div><!-- subcolumns -->
