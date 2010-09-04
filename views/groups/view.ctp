<h1><?php printf(__('Group %s', true), $this->data['Group']['name']); ?></h1>

<?php echo $session->flash() ?>

<h2><?php __('Details'); ?></h2>

<h3><?php __('Description'); ?></h3>

<p><?php 
  if (empty($this->data['Group']['description'])) {
    __("This group has no description");
  } else {
    echo h($this->data['Group']['description']); 
  }
  printf(__(" by %s", true), $html->link($this->data['User']['username'], "/users/view/{$this->data['User']['username']}"));
  if ($currentUser['User']['role'] >= ROLE_ADMIN || $currentUser['User']['id'] == $this->data['Group']['user_id']) {
    echo ' ' . $html->link(__('Edit this', true), "edit/{$this->data['Group']['name']}");
  }
?></p>

<ul class="bare">
<?php 
  $iconYes = $html->image('icons/accept.png', array('alt' => '+', 'title' => '+')) . ' ';
  $iconNo = $html->image('icons/delete.png', array('alt' => '-', 'title' => '-')) . ' ';
  if ($this->data['Group']['is_hidden']) {
    echo $html->tag('li', $iconNo . __("The group is hidden", true));
  } else {
    echo $html->tag('li', $iconYes . __("The group is shown with the media", true));
  }
  if ($this->data['Group']['is_moderated']) {
    echo $html->tag('li', $iconNo . __("The group subscription requires a confirmation", true));
  } else {
    echo $html->tag('li', $iconYes . __("The group subscription is free and does not need a confirmation", true));
  }
  if ($this->data['Group']['is_shared']) {
    echo $html->tag('li', $iconYes . __("This group is shared and can be used by other members", true));
  } else {
    echo $html->tag('li', $iconNo . __("This group is not shared", true));
  }
?>
</ul>

<h2><?php __('Member List'); ?></h2>
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
  foreach ($this->data['Member'] as $member) {
    $cells[] = array(
      $html->link($member['username'], "/users/view/{$member['username']}"),
      $html->link($member['username'], "/users/view/{$member['username']}")
      );
  }
  echo $html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?> 
</tbody>
</table>

<?php 
  $userId = $this->Session->read('User.id');
  $memberIds = Set::extract('/Member/id', $this->data);
  if (in_array($userId, $memberIds)) {
    echo $html->link(__('Unsubscribe', true), "unsubscribe/{$this->data['Group']['name']}"); 
  } else {
    echo $html->link(__('Subscribe', true), "subscribe/{$this->data['Group']['name']}"); 
  }
?>

<?php if ($media): ?>
<h2><?php __("Example Media"); ?></h2>
<?php 
  foreach($media as $m) {
    echo $imageData->mediaLink($m, 'mini');
  }
?>
<?php endif; ?>
