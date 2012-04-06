<h1><?php echo __('System overview'); ?></h1>
<table class='default'>
<thead>
<?php
  echo $this->Html->tableHeaders(array(__('Type'), __('Amount')));
?>
</thead>
<tbody>
<?php
  $cells = array(
    array(__('Users'), $data['users']),
    array(__('Guests'), $data['guests']),
    array(__('Groups'), $data['groups']),
    array(__('Files'), $data['files']),
    array(__('External files'), $data['files.external']),
    array(__('Sum of file size'), $data['file.size']),
    array(__('Sum of external file size'), $data['file.size.external']),
    array(__('Media'), $data['media']),
    array(__('Image media'), $data['media.images']),
    array(__('Video media'), $data['media.videos']),
    array(__('Video duration'), $data['media.video.length']),
    array(__('Comments'), $data['comments']),
    array(__('Tags'), $data['tags']),
    array(__('Categories'), $data['categories']),
    array(__('Locations'), $data['locations']),
  );
    
  echo $this->Html->tableCells($cells);
?>
</tbody>
</table>
