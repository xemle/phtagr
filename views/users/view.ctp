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
    __('User', true),
    __('Description', true),
    __('Action', true),
    );
  echo $html->tableHeaders($headers);
?>
</thead>

<tbody>
<?php 
  $cells = array();
  $groupIds = Set::extract('/Group/id', $this->data);
  //debug(Set::extract('/User[id=1]/username', $users));
  foreach ($this->data['Group'] as $group) {
    $username = implode('', Set::extract("/User[id={$group['user_id']}]/username", $users));
    $cells[] = array(
      $html->link($group['name'], "/groups/view/{$group['name']}"),
      $html->link($username, "/user/view/$username"),
      $text->truncate($group['description'], 30, array('ending' => '...', 'exact' => false, 'html' => false)),
      $html->link("View media", "/explorer/group/{$group['name']}")
      );
  }
  foreach ($this->data['Member'] as $group) {
    if (in_array($group['id'], $groupIds)) {
      continue;
    }
    $username = implode('', Set::extract("/User[id={$group['user_id']}]/username", $users));
    $cells[] = array(
      $html->link($group['name'], "/groups/view/{$group['name']}"),
      $html->link($username, "/user/view/$username"),
      $text->truncate($group['description'], 30, array('ending' => '...', 'exact' => false, 'html' => false)),
      $html->link("View media", "/explorer/group/{$group['name']}")
      );
  }

  function compareCells($a, $b) {
    if (strtolower($a[0]) == strtolower($b[0])) {
      return 0;
    } elseif (strtolower($a[0]) < strtolower($b[0])) {
      return -1;
    } else {
      return 1;
    }
  }
  usort($cells, 'compareCells');
  echo $html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?> 
</tbody>
</table>
