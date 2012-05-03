<h1><?php echo h($option->get('home.welcomeText', __("Welcome to phTagr"))); ?></h1>

<h3><?php echo __("Random Media"); ?></h3>
<?php
  if (count($randomMedia)) {
    $media = $randomMedia[0];
    $params = '/'.$this->Search->serialize(array('sort' => 'random'));

    $size = $this->ImageData->getimagesize($media, OUTPUT_SIZE_PREVIEW);
    echo $this->Html->tag('a',
      $this->Html->tag('img', '', array(
        'src' => Router::url("/media/preview/" . $media['Media']['id']),
        'alt' => $media['Media']['name'],
        'style' => "width: 100%; max-width: {$size[0]}px"
        )
      ), array(
        'href' => Router::url("/images/view/" . $media['Media']['id'] . "/sort:random")
      ));

    $link = $this->Search->getUri(array('sort' => 'random'));
    echo "<p>" . sprintf(__("See more %s"), $this->Html->link(__('random media...'), $link))."</p>";
  }
?>

<h3><?php echo __("Newest Media"); ?></h3>
<?php
  $cells = array();
  $i = 0;
  $keys = array_keys($newMedia);
  while (isset($keys[$i])) {
    $pos = $keys[$i] + 1;
    $media = $newMedia[$keys[$i]];
    $page = ceil($pos / $this->Search->getShow(12));
    $params = '/'.$this->Search->serialize(array('sort' => 'newest', 'page' => $page, 'pos' => $pos), false, false, array('defaults' => array('pos' => 1)));
    $cells[] = $this->Html->tag('a',
      $this->Html->tag('img', '', array(
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
  $link = $this->Search->getUri(array('sort' => 'newest'));
  echo "<p>" . sprintf(__("See %s"), $this->Html->link(__('all new media...'), $link))."</p>";
?>
<?php endif; ?>
