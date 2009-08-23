<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/" >
<channel>
  <title><?php echo $_SERVER['SERVER_NAME']; ?> Media RSS</title>
  <link><?php echo Router::url('/', true); ?></link>
  <description>Media RSS of phTagr (<?php echo $_SERVER['SERVER_NAME']; ?>)</description>
<?php $search->initialize(); ?>
<?php if ($navigator->hasPrev()): ?>
  <atom:link rel="previous" href="<?php echo Router::url($search->getUrl(false, array('page' => $search->getPage(1) - 1, false, array('baseUrl' => '/explorer/media/'))).'/media.rss', true); ?>" />
<?php endif; ?>
<?php if ($navigator->hasNext()): ?>
  <atom:link rel="next" href="<?php echo Router::url($search->getUrl(false, array('page' => $search->getPage(1) + 1, false, array('baseUrl' => '/explorer/media/'))).'/media.rss', true); ?>" />
<?php endif; ?>
<?php
  $keyParam = "";
  if ($session->check('Authentication.key')) {
    $keyParam = "key:".$session->read('Authentication.key').'/';
  }
  $quality = 'preview';
  if (isset($this->params['named']['quality']) && 
    $this->params['named']['quality'] == 'high') {
    $quality = 'high';
  }
?>
<?php foreach ($this->data as $media): ?>
  <item>
    <title><?php echo $media['Media']['name']." by ".$media['User']['username']; ?></title>
    <link><?php 
      $url = "/images/view/{$media['Media']['id']}";
      echo Router::url($url.'/'.$keyParam.$search->serialize(), true); ?></link>
    <?php 
      $thumbSize = $imageData->getimagesize($media, OUTPUT_SIZE_THUMB);
      $previewSize = $imageData->getimagesize($media, OUTPUT_SIZE_PREVIEW);
      $thumbUrl = "/media/thumb/{$media['Media']['id']}/$keyParam{$media['Media']['name']}";
      if ($media['Media']['canReadOriginal'] && $quality == 'high') {
        $contentUrl = "/media/high/{$media['Media']['id']}/$keyParam{$media['Media']['name']}";
      } else {
        $contentUrl = "/media/preview/{$media['Media']['id']}/$keyParam{$media['Media']['name']}";
      }
    ?>
    <media:thumbnail url="<?php echo Router::url($thumbUrl, true); ?>" <?php echo $thumbSize[3]; ?> />
    <media:content url="<?php echo Router::url($contentUrl, true); ?>" <?php echo $previewSize[3]; ?> />
    <guid><?php echo Router::url("/media/view/{$media['Media']['id']}", true); ?></guid>
    <description type="html" />
  </item>
<?php endforeach; ?>
</channel>
</rss>
