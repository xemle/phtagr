<h1><?php echo $data['Image']['name'] ?></h1>
<?php 
  $size = $imageData->getimagesize($data, IMAGE_SIZE_PREVIEW);
  echo "<img src=\"".Router::url("/files/preview/".$data['Image']['id'])."\" $size[3]/>"; ?>
<div>

<div class="meta">
<table>
  <tr>
    <td>
      <?php echo _("Date:"); ?>
    </td>
    <td>
      <?php 
        echo $html->link($data['Image']['date'],
          '/explorer/date'.
            '/from:'.$imageData->toUnix($data, -3*60*60).
            '/to:'.$imageData->toUnix($data, 3*60*60));
        echo " [";
        echo $html->link('<', 
          '/explorer/date'.
            '/to:'.$imageData->toUnix($data).
            '/sort:date', array('title'=>'Show all images before'));
        echo $html->link('d', 
          '/explorer/date'.
            '/from:'.$imageData->toUnix($data, -12*60*60).
            '/to:'.$imageData->toUnix($data, 12*60*60));
        echo $html->link('w',
          '/explorer/date'.
            '/from:'.$imageData->toUnix($data, -3.5*24*60*60).
            '/to:'.$imageData->toUnix($data, 3.5*24*60*60));
        echo $html->link('m',
          '/explorer/date'.
            '/from:'.$imageData->toUnix($data, -15*24*60*60).
            '/to:'.$imageData->toUnix($data, 15*24*60*60));
        echo $html->link('>',
          '/explorer/date'.
            '/from:'.$imageData->toUnix($data).
            '/sort:-date'); 
        echo "]";
      ?>
    </td>
  </tr>
  <?php if (count($data['Tag'])): ?>
  <tr>
    <td>
      <?php echo _("Tags:"); ?>
    </td>
    <td>
      <?php 
        $links = array();
        foreach ($data['Tag'] as $tag)
          $links[] = $html->link($tag['name'], '/explorer/tag/'.$tag['name']);
        echo implode(', ', $links);
      ?>
    </td>
  </tr>
  <?php endif; ?>
  <?php if (count($data['Category'])): ?>
  <tr>
    <td>
      <?php echo _("Categories:"); ?>
    </td>
    <td>
      <?php 
        $links = array();
        foreach ($data['Category'] as $category)
          $links[] = $html->link($category['name'], '/explorer/category/'.$category['name']);
        echo implode(', ', $links);
      ?>
    </td>
  </tr>
  <?php endif; ?>
  <?php if (count($data['Location'])): ?>
  <tr>
    <td>
      <?php echo _("Location:"); ?>
    </td>
    <td>
      <?php 
        $links = array();
        foreach ($data['Location'] as $location)
          $links[] = $html->link($location['name'], '/explorer/location/'.$location['name']);
        echo implode(', ', $links);
      ?>
    </td>
  </tr>
  <?php endif; ?>
</table>
</div>
</div>
<?php //pr($data); ?>
