<h1><?php printf(__('Group %s', true), $this->data['Group']['name']); ?></h1>

<?php echo $session->flash() ?>

<h2><?php __('Details'); ?></h2>

<h3><?php __('Description'); ?></h3>

<p><?php 
  if (empty($this->data['Group']['description'])) {
    __("This group has no description");
  } else {
    echo h($this->data['Group']['description']); 
  } ?></p>
<p><?php
  printf(__("The group is owned by user %s and has %d media in total.", true), $html->link($this->data['User']['username'], "/users/view/{$this->data['User']['username']}"), $mediaCount);
  if ($this->data['Group']['is_admin']) {
    echo ' ' . $html->link(__('Edit', true), "edit/{$this->data['Group']['name']}");
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

<?php 
  if ($this->data['Group']['is_admin']) {
    echo $form->create('Group', array('action' => 'addMember'));
    echo "<fieldset><legend>" . __("Add user", true) . "</legend>";
    echo $form->input('Member.new', array('label' => __("Username", true), 'secure' => false));
    echo "</fieldset>";
    echo $html->tag('ul', 
      $html->tag('li', $form->submit(__('Add', true)), array('escape' => false)),
      array('class' => 'buttons', 'escape' => false));
    echo $form->end();
  } else {
    $userId = $this->Session->read('User.id');
    $memberIds = Set::extract('/Member/id', $this->data);
    if (in_array($userId, $memberIds)) {
      echo $html->link(__('Unsubscribe', true), "unsubscribe/{$this->data['Group']['name']}"); 
    } else {
      echo $html->link(__('Subscribe', true), "subscribe/{$this->data['Group']['name']}"); 
    }
  }
?>

<?php if ($media): ?>
<h2><?php __("Recent Media"); ?></h2>
<p><?php 
  foreach($media as $m) {
    echo $imageData->mediaLink($m, 'mini');
  }
?></p>
<p><?php printf(__('See all media of the group %s', true), $html->link($this->data['Group']['name'], "/explorer/group/{$this->data['Group']['name']}")); ?></p>
<?php endif; ?>
