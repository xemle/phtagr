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
<p><?php __('User list is empty'); ?></p>
<?php endif; ?>
