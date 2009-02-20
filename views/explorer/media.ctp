<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/" >
<channel>
  <title>phTagr Media RSS</title>
  <link><?php echo Router::url('/', true); ?></link>
  <description>Media RSS of phTagr</description>
<?php if ($query->hasPrev()): ?>
  <atom:link rel="previous" href="<?php echo Router::url($query->getPrevUrl('/explorer/media/').'/media.rss', true); ?>" />
<?php endif; ?>
<?php if ($query->hasNext()): ?>
  <atom:link rel="next" href="<?php echo Router::url($query->getNextUrl('/explorer/media/').'/media.rss', true); ?>" />
<?php endif; ?>
<?php
  $query->initialize(); 
  if ($session->check('Authentication.key')) {
    $optParams = 'key:'.$session->read('Authentication.key').'/';
  } else {
    $optParams = '';
  }
?>
<?php foreach ($this->data as $medium): ?>
  <item>
    <title><?php echo $medium['Medium']['name']; ?></title>
    <link><?php 
      $url = '/images/view/'.$medium['Medium']['id'].'/'.$optParams;
      if ($query->getParams()) {
        $url .= $query->getParams().'/';
      }
      echo Router::url($url, true); ?></link>
    <?php 
      $thumbSize = $imageData->getimagesize($medium, OUTPUT_SIZE_THUMB);
      $previewSize = $imageData->getimagesize($medium, OUTPUT_SIZE_PREVIEW);
      $thumbUrl = '/media/thumb/'.$medium['Medium']['id'].'/'.$optParams.$medium['Medium']['file'];
      if ($medium['Medium']['canReadOriginal']) {
        $contentUrl = '/media/high/'.$medium['Medium']['id'].'/'.$optParams.$medium['Medium']['file'];
      } else {
        $contentUrl = '/media/preview/'.$medium['Medium']['id'].'/'.$optParams.$medium['Medium']['file'];
      }
    ?>
    <media:thumbnail url="<?php echo Router::url($thumbUrl, true); ?>" <?php echo $thumbSize[3]; ?> />
    <media:content url="<?php echo Router::url($contentUrl, true); ?>" <?php echo $previewSize[3]; ?> />
    <guid><?php echo Router::url($url, true); ?></guid>
    <description type="html" />
  </item>
<?php endforeach; ?>
</channel>
</rss> 
