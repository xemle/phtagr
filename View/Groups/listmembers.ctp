<h1><?php echo __('Group %s', $this->request->data['Group']['name']); ?></h1>

<?php echo $this->Session->flash() ?>

<h2><?php echo __('Member List'); ?></h2>
<table class="default">
<thead>
<?php
  $headers = array(
    __('Member'),
    );
  if ($this->request->data['Group']['is_admin']) {
    $headers[] = __('Action');
  }
  echo $this->Html->tableHeaders($headers);
?>
</thead>

<tbody>
<?php
  $cells = array();
  foreach ($this->request->data['Member'] as $member) {
    $actions = array();
    if ($this->request->data['Group']['is_admin']) {
      $actions[] = $this->Html->link(
        $this->Html->image('icons/delete.png',
          array(
            'alt' => __('Delete'),
            'title' => __("Unsubscribe '%s'", $member['username'])
          )
        ), "deleteMember/{$this->request->data['Group']['name']}/{$member['username']}", array('escape' => false));
    }
    $row = array(
      $this->Html->link($member['username'], "/users/view/{$member['username']}"),
      );
    if ($actions) {
      $row[] = implode(' ', $actions);
    }
    $cells[] = $row;
  }
  echo $this->Html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?>
</tbody>
</table>
