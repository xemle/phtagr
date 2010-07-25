<h1>Explorer</h1>
<?php echo $session->flash(); ?>

<?php $search->initialize(); ?>
<?php if ($navigator->hasPages()): ?>
<div class="paginator"><div class="subpaginator">
<?php echo $navigator->prev().' '.$navigator->numbers().' '.$navigator->next(); ?>
</div></div>
<?php endif; ?>

<?php 
  $pos = ($search->getPage(1)-1) * $search->getShow(12) + 1; 
?>
<?php foreach($this->data as $media): ?>
<h3><?php echo $media['Media']['name']; ?></h3>
<?php
  $date = $html->link(
    $time->relativeTime($media['Media']['date']),
    '/explorer/date/' . date("Y/m/d", strtotime($media['Media']['date']))
    );
  echo '<p>by ' . $html->link($media['User']['username'], "/explorer/user/".$media['User']['username']) . ' ' . $date . '</p>';
  $params = $search->serialize(false, array('pos' => $pos++), false, array('defaults' => array('pos' => 1)));
  echo $imageData->mediaLink($media, array('type' => 'thumb', 'div' => 'thumb', 'params' => ($params ? '/' . $params : false)));
  echo "<div class='meta-info'>";
  $names = Set::extract('/Tag/name', $media);
  $links = array();
  foreach($names as $name) {
    $links[] = $html->link($name, "tag/$name");
  }
  if (count($names)) {
    echo '<p>' . __("Tags", true) . ': ' . implode(', ', $links) . '</p>';
  }

  $names = Set::extract('/Category/name', $media);
  $links = array();
  foreach($names as $name) {
    $links[] = $html->link($name, "category/$name");
  }
  if (count($names)) {
    echo '<p>' . __("Categories", true) . ': ' . implode(', ', $links) . '</p>';
  }

  $names = Set::extract('/Location/name', $media);
  $links = array();
  foreach($names as $name) {
    $links[] = $html->link($name, "location/$name");
  }
  if (count($names)) {
    echo '<p>' . __("Location", true) . ': ' . implode(', ', $links) . '</p>';
  }
  echo "</div>";
?>
<?php endforeach; ?>

<?php if ($navigator->hasPages()): ?>
<div class="paginator"><div class="subpaginator">
<?php echo $navigator->prev().' '.$navigator->numbers().' '.$navigator->next(); ?>
</div></div>
<?php endif; ?>
