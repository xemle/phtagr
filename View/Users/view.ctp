<h1><?php 
  __('User %s', $this->data['User']['username']);  
  if ($currentUser['User']['role'] >= ROLE_SYSOP) {
    echo " " . $this->Html->link(__("Edit"), array('action' => 'edit', 'admin' => true, $this->data['User']['id']));
  }
?></h1>

<?php echo $this->Session->flash() ?>

<h2><?php echo __('User Details'); ?></h2>

<table class="default">
<thead>
<?php 
  $headers = array(
    __('Description'),
    __('Value'),
    );
  echo $this->Html->tableHeaders($headers);
?>
</thead>

<tbody>
<?php 
  $cells = array();
  $cells[] = array(__("Member since"), $this->Time->relativeTime($this->data['User']['created']));
  $cells[] = array(__("Count of media"), $this->data['Media']['count']);
  $cells[] = array(__("Count of files"), $this->data['File']['count']);
  $cells[] = array(__("Size of files"), $this->Number->toReadableSize($this->data['File']['bytes']));
  echo $this->Html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?> 
</tbody>
</table>

<h2><?php echo __('Group List'); ?></h2>
<table class="default">
<thead>
<?php 
  $headers = array(
    __('Group'),
    __('User'),
    __('Description'),
    __('Action'),
    );
  echo $this->Html->tableHeaders($headers);
?>
</thead>

<tbody>
<?php 
  $cells = array();
  $groupIds = Set::extract('/Group/id', $this->data);
  foreach ($this->data['Group'] as $group) {
    $username = implode('', Set::extract("/User[id={$group['user_id']}]/username", $users));
    $cells[] = array(
      $this->Html->link($group['name'], "/groups/view/{$group['name']}"),
      $this->Html->link($username, "/user/view/$username"),
      $text->truncate($group['description'], 30, array('ending' => '...', 'exact' => false, 'html' => false)),
      $this->Html->link("View media", "/explorer/group/{$group['name']}")
      );
  }
  foreach ($this->data['Member'] as $group) {
    if (in_array($group['id'], $groupIds)) {
      continue;
    }
    $username = implode('', Set::extract("/User[id={$group['user_id']}]/username", $users));
    $cells[] = array(
      $this->Html->link($group['name'], "/groups/view/{$group['name']}"),
      $this->Html->link($username, "/users/view/$username"),
      $text->truncate($group['description'], 30, array('ending' => '...', 'exact' => false, 'html' => false)),
      $this->Html->link("View media", "/explorer/group/{$group['name']}")
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
  echo $this->Html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?> 
</tbody>
</table>

<?php if ($media): ?>
<h2><?php echo __("Recent Media"); ?></h2>
<p><?php
  foreach($media as $m) {
    echo $this->ImageData->mediaLink($m, 'mini');
  } 
?></p>
<p><?php echo __('See all media of user %s', $this->Html->link($this->data['User']['username'], "/explorer/user/{$this->data['User']['username']}")); ?></p>
<?php endif; ?>
