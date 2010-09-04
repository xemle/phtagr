<h1><?php __('Users'); ?></h1>

<?php echo $session->flash(); ?>

<?php if (!empty($this->data)): ?>
<table class="default">
<thead>
<?php
  $headers = array(
    __('Name', true),
    __('Member Since', true)
    );
  echo $html->tableHeaders($headers);
?>
</thead>

<tbody>
<?php
  $cells = array();

  foreach($this->data as $user) {
    $row = array(
      $html->link($user['User']['username'], "view/{$user['User']['username']}"),
      $time->relativeTime($user['User']['created']),
      );
    $cells[] = $row;
  }
  echo $html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?>
</tbody>
</table>
<?php else: ?>
<div class="info">
<?php __('Currently no image groups are assigned. At the one hand each image could be assigned to a specific group. On the other hand a guest can be member of a set of groups. The guest is than able to view the images from his groups.'); ?>
</div>
<?php endif; ?>
