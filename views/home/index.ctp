<h1>Welcome to phTagr</h1>

<div class="subcolumns">
  <div class="c50l">
    <div class="subcl">
      <h3>Random Media</h3>
      <?php 
        if (isset($randomMedia)) {
          echo $imageData->mediaLink($randomMedia[0], array('type' => 'preview', 'size' => 340, 'div' => 'image'));
        } 
      ?>
    </div>
  </div>

  <div class="c50r">
    <div class="subcr">
      <h3>Recent added Media</h3>
      <?php
        $cells = array();
        $i = 0;
        for ($c = 0; $c < 3; $c++) {
          $row = array();
          for ($r = 0; $r < 4; $r++) {
            if (!isset($newMedia[$i])) {
              continue;
            }
            $row[] = $imageData->mediaLink($newMedia[$i++], array('type' => 'mini', 'div' => 'image'));
          }
          $cells[] = $row;
        }
      ?> 
      <table class="bare">
        <tbody>
          <?php echo $html->tableCells($cells); ?>
        </tbody>
      </table>
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
    <h3>Popular Categories</h3>
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
    </div>
  </div>
</div><!-- subcolumns -->


