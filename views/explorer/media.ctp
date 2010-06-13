<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/" >
<channel>
  <title><?php echo $_SERVER['SERVER_NAME']; ?> Media RSS</title>
  <link><?php echo Router::url('/', true); ?></link>
  <description>Media RSS of phTagr (<?php echo Router::url('/', true); ?>)</description>
<?php 
  $search->initialize(array('baseUri' => '/explorer/media', 'afterUri' => '/media.rss', 'defaults' => array('pos' => 1))); 
?>
<?php
  $keyParam = '';
  if ($session->check('Authentication.key')) {
    $search->setKey($session->read('Authentication.key'));
    $keyParam = '/key:' . $session->read('Authentication.key');
  }
  $quality = 'preview';
  if (isset($this->params['named']['quality']) && 
    $this->params['named']['quality'] == 'high') {
    $search->setQuality('high');
    $quality = 'high';
  }
?>
  <atom:link rel="self" href="<?php echo Router::url($search->getUri(), true); ?>" />
<?php if ($navigator->hasPrev()): ?>
  <atom:link rel="previous" href="<?php echo Router::url($search->getUri(false, array('page' => $search->getPage(1) - 1)), true); ?>" />
<?php endif; ?>
<?php if ($navigator->hasNext()): ?>
  <atom:link rel="next" href="<?php echo Router::url($search->getUri(false, array('page' => $search->getPage(1) + 1)), true); ?>" />
<?php endif; ?>
<?php 
  $offset = $search->getShow() * ($search->getPage(1) - 1) + 1;
  foreach ($this->data as $media): ?>
  <item>
    <title><?php echo $media['Media']['name']." by ".$media['User']['username']; ?></title>
    <link><?php 
      $url = "/images/view/{$media['Media']['id']}/";
      echo Router::url($url . $search->serialize(false, array('pos' => $offset++), array('quality')), true); ?></link>
    <?php 
      $thumbSize = $imageData->getimagesize($media, OUTPUT_SIZE_THUMB);
      $thumbUrl = sprintf("/media/thumb/%d%s/%s", $media['Media']['id'], $keyParam, $media['Media']['name']);

      if ($media['Media']['canReadOriginal'] && $quality == 'high') {
        $contentUrl = sprintf("/media/high/%d%s/%s", $media['Media']['id'], $keyParam, $media['Media']['name']);
        $previewSize = $imageData->getimagesize($media, OUTPUT_SIZE_HIGH);
      } else {
        $contentUrl = sprintf("/media/preview/%d%s/%s", $media['Media']['id'], $keyParam, $media['Media']['name']);
        $previewSize = $imageData->getimagesize($media, OUTPUT_SIZE_PREVIEW);
      }
    ?><media:thumbnail url="<?php echo Router::url($thumbUrl, true); ?>" <?php echo $thumbSize[3]; ?> />
    <media:content url="<?php echo Router::url($contentUrl, true); ?>" <?php echo $previewSize[3]; ?> />
    <guid><?php echo Router::url("/media/view/{$media['Media']['id']}", true); ?></guid>
    <?php 
      if ($media['Media']['caption']) {
        echo $html->tag('description', $media['Media']['caption']); 
      } else {
        echo "<description />\n";
      }
    ?>
  </item>
<?php endforeach; ?>
</channel>
</rss>
