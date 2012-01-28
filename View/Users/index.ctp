<h1><?php 
  __('Users'); 
  if ($isAdmin) {
    echo ' ' . $this->Html->link(__('Admin List'), array('admin' => true, 'action' => 'index'));
  }
?></h1>

<?php echo $this->Session->flash(); ?>

<?php if (!empty($this->request->data)): ?>
<table class="default">
<thead>
<?php
  $headers = array(
    __('Name'),
    __('Member Since')
    );
  echo $this->Html->tableHeaders($headers);
?>
</thead>

<tbody>
<?php
  $cells = array();

  foreach($this->request->data as $user) {
    $row = array(
      $this->Html->link($user['User']['username'], "view/{$user['User']['username']}"),
      $this->Time->timeAgoInWords($user['User']['created']),
      );
    $cells[] = $row;
  }
  echo $this->Html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?>
</tbody>
</table>
<?php else: ?>
<p><?php echo __('User list is empty'); ?></p>
<?php endif; ?>
