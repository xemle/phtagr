<h1><?php echo __("File Overview"); ?></h1>

<table class="default">
<thead>
  <?php echo $this->Html->tableHeaders(array(__('Description'), __('Value'))); ?>
</thead>
<tbody>
<?php
  $cells = array();
  $cells[] = array(__('Files total'), $files['count']);
  $cells[] = array(__('Count of images/videos'), $files['active']);
  if ($files['external']) {
    $cells[] = array(__('External images/videos'), $files['external']);
    $cells[] = array(__('Bytes total (with external sources)'), sprintf("%s (%s)", $this->Number->toReadableSize($files['bytes']), $this->Number->toReadableSize($files['bytesAll'])));
  } else {
    $cells[] = array(__('Bytes total'), $this->Number->toReadableSize($files['bytes']));
  }
  if ($files['quota']) {
    $cells[] = array(__('Quota (free)'), sprintf("%s (%s)", $this->Number->toReadableSize($files['quota']), $this->Number->toReadableSize($files['free'])));
  }
  $cells[] = array(__('Videos'), $files['video']);
  $cells[] = array(__('Public files'), $files['public']);
  $cells[] = array(__('Visible for users'), $files['user']);
  $cells[] = array(__('Visible for group members'), $files['group']);
  $cells[] = array(__('Private files'), $files['private']);
  $cells[] = array(__('Unsynced files'), $files['dirty']);
  echo $this->Html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?>
</tbody>
</table>
