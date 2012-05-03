<h1><?php echo __('System overview'); ?></h1>
<table class='default'>
<thead>
<?php
  echo $this->Html->tableHeaders(array(__('Type'), __('Amount')));
?>
</thead>
<tbody>
<?php
  $duration = $data['media.video.length'];
  $durationHours = sprintf('%02dh %02dm %02ds', intval($duration / 3600), intval(($duration % 3600) / 60), $duration % 60);
  $cells = array(
    array(__('Users'), $data['users']),
    array(__('Guests'), $data['guests']),
    array(__('Groups'), $data['groups']),
    array(__('Files'), $data['files']),
    array(__('External files'), $data['files.external']),
    array(__('Sum of file size'), $this->Number->toReadableSize($data['file.size'])),
    array(__('Sum of external file size'), $this->Number->toReadableSize($data['file.size.external'])),
    array(__('Media'), $data['media']),
    array(__('Image media'), $data['media.images']),
    array(__('Video media'), $data['media.videos']),
    array(__('Video duration'), $durationHours),
    array(__('Comments'), $data['comments']),
    array(__('Tags'), $data['tags']),
    array(__('Categories'), $data['categories']),
    array(__('Locations'), $data['locations']),
  );

  echo $this->Html->tableCells($cells);
?>
</tbody>
</table>
