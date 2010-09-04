<h1><?php printf(__('User %s', true), $this->data['User']['username']); ?></h1>

<?php echo $session->flash() ?>

<h2><?php __('User Details'); ?></h2>

<table class="default">
<thead>
<?php 
  $headers = array(
    __('Description', true),
    __('Value', true),
    );
  echo $html->tableHeaders($headers);
?>
</thead>

<tbody>
<?php 
  $cells = array();
  $cells[] = array(__("Member since", true), $this->Time->relativeTime($this->data['User']['created']));
  $cells[] = array(__("Count of media", true), $this->data['Media']['count']);
  $cells[] = array(__("Count of files", true), $this->data['File']['count']);
  $cells[] = array(__("Size of files", true), $this->Number->toReadableSize($this->data['File']['bytes']));
  echo $html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?> 
</tbody>
</table>

<h2><?php __('Group List'); ?></h2>
<table class="default">
<thead>
<?php 
  $headers = array(
    __('Member', true),
    __('Action', true),
    );
  echo $html->tableHeaders($headers);
?>
</thead>

<tbody>
<?php 
  $cells = array();
  foreach ($this->data['Member'] as $group) {
    $cells[] = array(
      $html->link($group['name'], "/groups/view/{$group['name']}"),
      $html->link("View media", "/explorer/group/{$group['name']}")
      );
  }
  echo $html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?> 
</tbody>
</table>
<?php debug($this->data); ?>
