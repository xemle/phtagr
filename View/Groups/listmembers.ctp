<h1><?php printf(__('Group %s', true), $this->data['Group']['name']); ?></h1>

<?php echo $session->flash() ?>

<h2><?php __('Member List'); ?></h2>
<table class="default">
<thead>
<?php 
  $headers = array(
    __('Member', true),
    );
  if ($this->data['Group']['is_admin']) {
    $headers[] = __('Action', true);
  }
  echo $html->tableHeaders($headers);
?>
</thead>

<tbody>
<?php 
  $cells = array();
  foreach ($this->data['Member'] as $member) {
    $actions = array();
    if ($this->data['Group']['is_admin']) {
      $actions[] = $html->link(
        $html->image('icons/delete.png', 
          array(
            'alt' => __('Delete', true), 
            'title' => sprintf(__("Unsubscribe '%s'", true), $member['username'])
          )
        ), "deleteMember/{$this->data['Group']['name']}/{$member['username']}", array('escape' => false));
    }
    $row = array(
      $html->link($member['username'], "/users/view/{$member['username']}"),
      );
    if ($actions) {
      $row[] = implode(' ', $actions);
    }
    $cells[] = $row;
  }
  echo $html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?> 
</tbody>
</table>
