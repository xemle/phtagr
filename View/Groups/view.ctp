<h1><?php echo __('Group %s', $this->request->data['Group']['name']); ?></h1>

<?php echo $this->Session->flash() ?>

<h2><?php echo __('Details'); ?></h2>

<h3><?php echo __('Description'); ?></h3>

<p><?php
  if (empty($this->request->data['Group']['description'])) {
    __("This group has no description");
  } else {
    echo h($this->request->data['Group']['description']);
  } ?></p>
<p><?php
  __("The group is owned by user %s and has %d media in total.", $this->Html->link($this->request->data['User']['username'], "/users/view/{$this->request->data['User']['username']}"), $mediaCount);
  if ($this->request->data['Group']['is_admin']) {
    echo ' ' . $this->Html->link(__('Edit'), "edit/{$this->request->data['Group']['name']}");
  }
?></p>

<ul class="bare">
<?php
  $iconYes = $this->Html->image('icons/accept.png', array('alt' => '+', 'title' => '+')) . ' ';
  $iconNo = $this->Html->image('icons/delete.png', array('alt' => '-', 'title' => '-')) . ' ';
  if ($this->request->data['Group']['is_hidden']) {
    echo $this->Html->tag('li', $iconNo . __("The group is hidden"));
  } else {
    echo $this->Html->tag('li', $iconYes . __("The group is shown with the media"));
  }
  if ($this->request->data['Group']['is_moderated']) {
    echo $this->Html->tag('li', $iconNo . __("The group subscription requires a confirmation"));
  } else {
    echo $this->Html->tag('li', $iconYes . __("The group subscription is free and does not need a confirmation"));
  }
  if ($this->request->data['Group']['is_shared']) {
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

<?php
  if ($this->request->data['Group']['is_admin']) {
    $groupId = $this->request->data['Group']['id'];
    echo $this->Form->create('Group', array('url' => "addMember/$groupId", 'type' => 'post'));
    echo "<fieldset><legend>" . __("Add user") . "</legend>";
    $this->Form->unlockField('Member.new');
    echo $this->Form->input('Member.new', array('label' => __("Username"), 'secure' => false));
    echo $this->Autocomplete->autoComplete('Member.new', '/groups/autocomplete', array('targetField' => 'User.username'));
    echo "</fieldset>";
    echo $this->Form->end(__('Add'));
  } else {
    $userId = $this->Session->read('User.id');
    $memberIds = Set::extract('/Member/id', $this->request->data);
    if (in_array($userId, $memberIds)) {
      echo $this->Html->link(__('Unsubscribe'), "unsubscribe/{$this->request->data['Group']['name']}");
    } else {
      echo $this->Html->link(__('Subscribe'), "subscribe/{$this->request->data['Group']['name']}");
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
<p><?php echo __('See all media of the group %s', $this->Html->link($this->request->data['Group']['name'], "/explorer/group/{$this->request->data['Group']['name']}")); ?></p>
<?php endif; ?>
