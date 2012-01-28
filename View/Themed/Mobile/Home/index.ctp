<h1><?php echo h($option->get('home.welcomeText', __("Welcome to phTagr", true))); ?></h1>

<h3><?php __("Random Media"); ?></h3>
<?php 
  if (count($randomMedia)) {
    $media = $randomMedia[0];
    $params = '/'.$search->serialize(array('sort' => 'random'));

    $size = $imageData->getimagesize($media, OUTPUT_SIZE_PREVIEW);
    echo $html->tag('a', 
      $html->tag('img', '', array(
        'src' => Router::url("/media/preview/" . $media['Media']['id']),
        'alt' => $media['Media']['name'],
        'style' => "width: 100%; max-width: {$size[0]}px"
        )
      ), array(
        'href' => Router::url("/images/view/" . $media['Media']['id'] . "/sort:random")
      ));
        
    $link = $search->getUri(array('sort' => 'random'));
    echo "<p>" . sprintf(__("See more %s", true), $html->link(__('random media...', true), $link))."</p>";
  } 
?>

<h3><?php __("Newest Media"); ?></h3>
<?php
  $cells = array();
  $i = 0;
  $keys = array_keys($newMedia);
  while (isset($keys[$i])) {
    $pos = $keys[$i] + 1;
    $media = $newMedia[$keys[$i]];
    $page = ceil($pos / $search->getShow(12));
    $params = '/'.$search->serialize(array('sort' => 'newest', 'page' => $page, 'pos' => $pos), false, false, array('defaults' => array('pos' => 1)));
    $cells[] = $html->tag('a', 
      $html->tag('img', '', array(
        'src' => Router::url("/media/mini/" . $media['Media']['id']) . $params,
        'alt' => $media['Media']['name'],
        'width' => 70,
        'height' => 70
        )
      ), array(
        'href' => Router::url("/images/view/" . $media['Media']['id'] . $params)
      ));    
    $i++;
  }
?> 
<?php if (count($cells)): ?>
<p><?php echo implode(' ', $cells); ?></p>
<?php
  $link = $search->getUri(array('sort' => 'newest'));
  echo "<p>" . sprintf(__("See %s", true), $html->link(__('all new media...', true), $link))."</p>";
?>
<?php endif; ?>
