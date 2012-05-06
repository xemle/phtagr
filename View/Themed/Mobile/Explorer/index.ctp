<h1><?php echo __('Explorer'); ?></h1>
<?php echo $this->Session->flash(); ?>

<?php $this->Search->initialize(); ?>
<?php if ($this->Navigator->hasPages()): ?>
<div class="paginator"><div class="subpaginator">
<?php echo $this->Navigator->prev().' '.$this->Navigator->numbers().' '.$this->Navigator->next(); ?>
</div></div>
<?php endif; ?>

<?php
  $pos = ($this->Search->getPage(1)-1) * $this->Search->getShow(12) + 1;
?>
<?php foreach($this->data as $media): ?>
<h3><?php echo $media['Media']['name']; ?></h3>
<?php
  $date = $this->Html->link(
    $this->Time->timeAgoInWords($media['Media']['date']),
    '/explorer/date/' . date("Y/m/d", strtotime($media['Media']['date']))
    );
  echo '<p>by ' . $this->Html->link($media['User']['username'], "/explorer/user/".$media['User']['username']) . ' ' . $date . '</p>';
  $params = $this->Search->serialize(false, array('pos' => $pos++), false, array('defaults' => array('pos' => 1)));
  echo $this->ImageData->mediaLink($media, array('type' => 'thumb', 'div' => 'thumb', 'params' => ($params ? '/' . $params : false)));
  echo "<div class='meta-info'>";
  $names = Set::extract('/Tag/name', $media);
  $links = array();
  foreach($names as $name) {
    $links[] = $this->Html->link($name, "tag/$name");
  }
  if (count($names)) {
    echo '<p>' . __("Tags") . ': ' . implode(', ', $links) . '</p>';
  }

  $names = Set::extract('/Category/name', $media);
  $links = array();
  foreach($names as $name) {
    $links[] = $this->Html->link($name, "category/$name");
  }
  if (count($names)) {
    echo '<p>' . __("Categories") . ': ' . implode(', ', $links) . '</p>';
  }

  $names = Set::extract('/Location/name', $media);
  $links = array();
  foreach($names as $name) {
    $links[] = $this->Html->link($name, "location/$name");
  }
  if (count($names)) {
    echo '<p>' . __("Location") . ': ' . implode(', ', $links) . '</p>';
  }
  echo "</div>";
?>
<?php endforeach; ?>

<?php if ($this->Navigator->hasPages()): ?>
<div class="paginator"><div class="subpaginator">
<?php echo $this->Navigator->prev().' '.$this->Navigator->numbers().' '.$this->Navigator->next(); ?>
</div></div>
<?php endif; ?>
