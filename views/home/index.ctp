<h1><?php echo h($option->get('home.welcomeText', __("Welcome to phTagr", true))); ?></h1>

<div class="subcolumns">
  <div class="c50l">
    <div class="subcl">
      <h3><?php __("Random Media"); ?></h3>
      <?php 
        if (count($randomMedia)) {
          $media = $randomMedia[0];
          $params = '/'.$search->serialize(array('sort' => 'random'));

          $cite = "<cite>" . sprintf(__("%s by %s", true), h($media['Media']['name']), $html->link($media['User']['username'], '/explorer/user/' . $media['User']['username'])) . "</cite>";

          echo $imageData->mediaLink($media, array('type' => 'preview', 'size' => 340, 'div' => 'image', 'params' => $params, 'after' => $cite));

          $link = $search->getUri(array('sort' => 'random'));
          echo "<p>" . sprintf(__("See more %s", true), $html->link(__('random media...', true), $link))."</p>";
        } 
      ?>
    </div>
  </div>

  <div class="c50r">
    <div class="subcr">
      <h3><?php __("Newest Media"); ?></h3>
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
    
            $pos = $keys[$i] + 1;
            $page = ceil($pos / $search->getShow(12));
            $params = '/'.$search->serialize(array('sort' => 'newest', 'page' => $page, 'pos' => $pos), false, false, array('defaults' => array('pos' => 1)));
            $row[] = $imageData->mediaLink($newMedia[$keys[$i++]], array('type' => 'mini', 'div' => 'image', 'params' => $params));
          }
          if (count($row)) {
            $cells[] = $row;
          }
        }
      ?> 
      <?php if (count($cells)): ?>
      <table class="bare">
        <tbody>
          <?php echo $html->tableCells($cells); ?>
        </tbody>
      </table>
      <?php
        $link = $search->getUri(array('sort' => 'newest'));
        echo "<p>" . sprintf(__("See %s", true), $html->link(__('all new media...', true), $link))."</p>";
      ?>
      <?php endif; ?>
    </div>
  </div>
</div><!-- subcolumns -->

<div class="subcolumns">
  <div class="c50l">
    <div class="subcl">
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
      <?php echo $text->truncate(preg_replace('/\n/', '<br />', $comment['Comment']['text']), 220, '...', false, true); ?>
      </div>
      </div><!-- comment -->
      <?php endforeach; /* comments */ ?>
      </div><!-- comments -->
      <div>
        <?php echo $html->link(__("Older comments...", true), "/comments", NULL, false, false);?>
      </div>
      <?php endif; ?>    
    </div>
  </div>

  <div class="c50r">
    <div class="subcr">
      <h3><?php __("Popular Tags"); ?></h3>
        <?php
        if (isset($cloudTags) && count($cloudTags)) {
          echo $cloud->cloud($cloudTags, '/explorer/tag/');
        } else {
          echo '<p>' . __("No tags assigned") . '</p>';
        }
        ?>
      <h3><?php __("Popular Categories"); ?></h3>
        <?php
        if (isset($cloudCategories) && count($cloudCategories)) {
          echo $cloud->cloud($cloudCategories, '/explorer/category/');
        } else {
          echo '<p>' . __("No categories assigned") . '</p>';
        }
        ?>
     </div>
  </div>
</div><!-- subcolumns -->
