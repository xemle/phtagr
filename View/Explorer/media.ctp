<?php
  $this->response->type('application/rss+xml');
  echo '<?xml version="1.0" encoding="UTF-8" ?>'."\n";
?><rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/"
  xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
  <generator>Gallery phTagr</generator>
  <title><?php echo $_SERVER['SERVER_NAME']; ?> Media RSS</title>
  <link><?php echo Router::url('/', true); ?></link>
  <description>Media RSS of phTagr (<?php echo Router::url('/', true); ?>)</description>
<?php
  $this->Search->initialize(array('baseUri' => '/explorer/media', 'afterUri' => '/media.rss', 'defaults' => array('pos' => 1, 'page' => 1)));
?>
<?php
  $keyParam = '';
  if ($this->Session->check('Authentication.key')) {
    $this->Search->setKey($this->Session->read('Authentication.key'));
    $keyParam = '/key:' . $this->Session->read('Authentication.key');
  }
  $quality = 'preview';
  if (isset($this->params['named']['quality']) &&
    $this->params['named']['quality'] == 'high') {
    $this->Search->setQuality('high');
    $quality = 'high';
  }
?>
  <atom:link rel="self" href="<?php echo Router::url($this->Search->getUri(), true); ?>" type="application/rss+xml" />
<?php if ($this->Navigator->hasPrev()): ?>
  <atom:link rel="previous" href="<?php echo Router::url($this->Search->getUri(false, array('page' => $this->Search->getPage(1) - 1)), true); ?>" type="application/rss+xml" />
<?php endif; ?>
<?php if ($this->Navigator->hasNext()): ?>
  <atom:link rel="next" href="<?php echo Router::url($this->Search->getUri(false, array('page' => $this->Search->getPage(1) + 1)), true); ?>" type="application/rss+xml" />
<?php endif; ?>
  <pubDate><?php echo date('r'); ?></pubDate>
<?php
  $offset = $this->Search->getShow() * ($this->Search->getPage(1) - 1) + 1;
  foreach ($this->request->data as $media): ?>
  <item>
    <title><?php echo $media['Media']['name']." by ".$media['User']['username']; ?></title>
    <link><?php
      $url = "/images/view/{$media['Media']['id']}/";
      echo Router::url($url . $this->Search->serialize(false, array('pos' => $offset++), array('quality')), true); ?></link>
    <guid isPermaLink="true"><?php echo Router::url("/images/view/{$media['Media']['id']}", true); ?></guid>
    <pubDate><?php echo date('r', strtotime($media['Media']['modified'])); ?></pubDate>
    <?php
      $thumbSize = $this->ImageData->getimagesize($media, OUTPUT_SIZE_THUMB);
      $thumbUrl = sprintf("/media/thumb/%d%s/%s", $media['Media']['id'], $keyParam, $media['Media']['name']);

      if ($media['Media']['canReadOriginal'] && $quality == 'high') {
        $contentUrl = sprintf("/media/high/%d%s/%s", $media['Media']['id'], $keyParam, $media['Media']['name']);
        $previewSize = $this->ImageData->getimagesize($media, OUTPUT_SIZE_HIGH);
      } else {
        $contentUrl = sprintf("/media/preview/%d%s/%s", $media['Media']['id'], $keyParam, $media['Media']['name']);
        $previewSize = $this->ImageData->getimagesize($media, OUTPUT_SIZE_PREVIEW);
      }
    ?><media:thumbnail url="<?php echo Router::url($thumbUrl, true); ?>" <?php echo $thumbSize[3]; ?> type="image/jpeg" />
    <media:content url="<?php echo Router::url($contentUrl, true); ?>" <?php echo $previewSize[3]; ?> type="image/jpeg" />
    <?php
      if ($media['Media']['caption']) {
        echo $this->Html->tag('description', $media['Media']['caption']);
      } else {
        echo '<description></description>';
      }
    ?>
  </item>
<?php endforeach; ?>
</channel>
</rss>
