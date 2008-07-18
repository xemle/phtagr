<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss" >
<channel>
  <title>phTagr</title>
  <link></link>
  <description>Media RSS of phTagr</description>
<?php if ($query->hasPrev()): ?>
  <atom:link rel="previous" href="<?php echo Router::url($query->getPrevUrl('/explorer/media/').'/media.rss', true); ?>" />
<?php endif; ?>
<?php if ($query->hasNext()): ?>
  <atom:link rel="next" href="<?php echo Router::url($query->getNextUrl('/explorer/media/').'/media.rss', true); ?>" />
<?php endif; ?>
<?php foreach ($this->data as $image): ?>
  <item>
    <title><?php echo $image['Image']['name']; ?></title>
    <link><?php echo Router::url('/images/view/'.$image['Image']['id'].'/'.$image['Image']['file'], true); ?></link>
    <?php 
      $thumbSize = $imageData->getimagesize($image, OUTPUT_SIZE_THUMB);
      $previewSize = $imageData->getimagesize($image, OUTPUT_SIZE_PREVIEW);
      $thumbUrl = '/files/thumb/'.$image['Image']['id'].'/'.$image['Image']['file'];
      if ($image['Image']['canReadOriginal']) {
        $contenUrl = '/files/high/'.$image['Image']['id'].'/'.$image['Image']['file'];
      } else {
        $contenUrl = '/files/preview/'.$image['Image']['id'].'/'.$image['Image']['file'];
      }
    ?>
    <media:thumbnail url="<?php echo Router::url($thumbUrl, true); ?>" <?php echo $thumbSize[3]; ?> />
    <media:content url="<?php echo Router::url($contenUrl, true); ?>" <?php echo $previewSize[3]; ?> />
    <guid><?php echo $image['Image']['id']; ?></guid>
    <description type="html" />
  </item>
<?php endforeach; ?>
</channel>
</rss> 
