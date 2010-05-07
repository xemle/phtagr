<h1><?php __("File Overview"); ?></h1>

<table class="default">
<thead>
  <?php echo $html->tableHeaders(array(__('Description', true), __('Value', true))); ?>
</thead>
<tbody>
<?php
  $cells = array();
  $cells[] = array(__('Files total', true), $files['count']);
  $cells[] = array(__('Count of images/videos', true), $files['active']);
  if ($files['external']) {
    $cells[] = array(__('External images/videos', true), $files['external']);
    $cells[] = array(__('Bytes total (with external sources)', true), sprintf("%s (%s)", $number->toReadableSize($files['bytes']), $number->toReadableSize($files['bytesAll'])));
  } else {
    $cells[] = array(__('Bytes total', true), $number->toReadableSize($files['bytes']));
  } 
  if ($files['quota']) {
    $cells[] = array(__('Quota (free)', true), sprintf("%s (%s)", $number->toReadableSize($files['quota']), $number->toReadableSize($files['free'])));
  }
  $cells[] = array(__('Videos', true), $files['video']);
  $cells[] = array(__('Public files', true), $files['public']);
  $cells[] = array(__('Visible for users', true), $files['user']);
  $cells[] = array(__('Visible for group members', true), $files['group']);
  $cells[] = array(__('Private files', true), $files['private']);
  $cells[] = array(__('Unsynced files', true), $files['dirty']);
  echo $html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?>
</tbody>
</table>
