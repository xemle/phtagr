<h1><?php echo __('Group %s', $this->data['Group']['name']); ?></h1>

<?php echo $this->Session->flash() ?>

<h2><?php echo __('Details'); ?></h2>

<h3><?php echo __('Description'); ?></h3>

<p><?php 
  if (empty($this->data['Group']['description'])) {
    __("This group has no description");
  } else {
    echo h($this->data['Group']['description']); 
  } ?></p>
<p><?php
  __("The group is owned by user %s and has %d media in total.", $this->Html->link($this->data['User']['username'], "/users/view/{$this->data['User']['username']}"), $mediaCount);
  if ($this->data['Group']['is_admin']) {
    echo ' ' . $this->Html->link(__('Edit'), "edit/{$this->data['Group']['name']}");
  }
?></p>

<ul class="bare">
<?php 
  $iconYes = $this->Html->image('icons/accept.png', array('alt' => '+', 'title' => '+')) . ' ';
  $iconNo = $this->Html->image('icons/delete.png', array('alt' => '-', 'title' => '-')) . ' ';
  if ($this->data['Group']['is_hidden']) {
    echo $this->Html->tag('li', $iconNo . __("The group is hidden"));
  } else {
    echo $this->Html->tag('li', $iconYes . __("The group is shown with the media"));
  }
  if ($this->data['Group']['is_moderated']) {
    echo $this->Html->tag('li', $iconNo . __("The group subscription requires a confirmation"));
  } else {
    echo $this->Html->tag('li', $iconYes . __("The group subscription is free and does not need a confirmation"));
  }
  if ($this->data['Group']['is_shared']) {
    echo $this->Html->tag('li', $iconYes . __("This group is shared and can be used by other members"));
  } else {
    echo $this->Html->tag('li', $iconNo . __("This group is not shared"));
  }
?>
</ul>

<h2><?php echo __('Member List'); ?></h2>
<table class="default">
<thead>
<?php 
  $headers = array(
    __('Member'),
    );
  if ($this->data['Group']['is_admin']) {
    $headers[] = __('Action');
  }
  echo $this->Html->tableHeaders($headers);
?>
</thead>

<tbody>
<?php 
  $cells = array();
  foreach ($this->data['Member'] as $member) {
    $actions = array();
    if ($this->data['Group']['is_admin']) {
      $actions[] = $this->Html->link(
        $this->Html->image('icons/delete.png', 
          array(
            'alt' => __('Delete'), 
            'title' => __("Unsubscribe '%s'", $member['username'])
          )
        ), "deleteMember/{$this->data['Group']['name']}/{$member['username']}", array('escape' => false));
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

<?php 
  if ($this->data['Group']['is_admin']) {
    echo $this->Form->create('Group', array('action' => 'addMember'));
    echo "<fieldset><legend>" . __("Add user") . "</legend>";
    echo $this->Form->input('Member.new', array('label' => __("Username"), 'secure' => false));
    echo "</fieldset>";
    echo $this->Html->tag('ul', 
      $this->Html->tag('li', $this->Form->submit(__('Add')), array('escape' => false)),
      array('class' => 'buttons', 'escape' => false));
    echo $this->Form->end();
  } else {
    $userId = $this->Session->read('User.id');
    $memberIds = Set::extract('/Member/id', $this->data);
    if (in_array($userId, $memberIds)) {
      echo $this->Html->link(__('Unsubscribe'), "unsubscribe/{$this->data['Group']['name']}"); 
    } else {
      echo $this->Html->link(__('Subscribe'), "subscribe/{$this->data['Group']['name']}"); 
    }
  }
?>

<?php if ($media): ?>
<h2><?php echo __("Recent Media"); ?></h2>
<p><?php 
  foreach($media as $m) {
    echo $this->ImageData->mediaLink($m, 'mini');
  }
?></p>
<p><?php echo __('See all media of the group %s', $this->Html->link($this->data['Group']['name'], "/explorer/group/{$this->data['Group']['name']}")); ?></p>
<?php endif; ?>
