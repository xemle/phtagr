<h1>File Overview</h1>

<table>
<tbody>
<?php
  $cells = array();
  $cells[] = array('Files total', $files['count']);
  $cells[] = array('Count of images/videos', $files['active']);
  if ($files['external']) {
    $cells[] = array('External images/videos', $files['external']);
    $cells[] = array('Bytes total (with external sources)', $number->toReadableSize($files['bytes'])." (".$number->toReadableSize($files['bytesAll']).")");
  } else {
    $cells[] = array('Bytes total', $number->toReadableSize($files['bytes']));
  } 
  if ($files['quota']) {
    $cells[] = array('Quota (free)', $number->toReadableSize($files['quota'])." (".$number->toReadableSize($files['free']).")");
  }
  $cells[] = array('Videos', $files['video']);
  $cells[] = array('Public files', $files['public']);
  $cells[] = array('Visible for users', $files['user']);
  $cells[] = array('Visible for group members', $files['group']);
  $cells[] = array('Private files', $files['private']);
  $cells[] = array('Unsynced files', $files['dirty']);
  echo $html->tableCells($cells);
?>
</tbody>
</table>
