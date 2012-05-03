<h1><?php echo __('Groups'); ?></h1>

<?php echo $this->Session->flash(); ?>

<?php if (!empty($this->request->data)): ?>
<table class="default">
<thead>
<?php
  $headers = array(
    __('Name'),
    __('From User'),
    __('Description'),
    __('Members'),
    __('Actions'));
  echo $this->Html->tableHeaders($headers);
?>
</thead>

<tbody>
<?php
  $cells = array();
  $myGroupIds = Set::extract("/Group/id", $currentUser);
  $memberIds = Set::extract("/Member/id", $currentUser);
  $isAdmin = $currentUser['User']['role'] >= ROLE_ADMIN;

  foreach($this->request->data as $group) {
    $actions = array();
    if ($currentUser['User']['id'] != $group['Group']['user_id']) {
      if (!in_array($group['Group']['id'], $memberIds)) {
        $actions[] = $this->Html->link(
          $this->Html->image('icons/group_add.png', array('alt' => __('Subscribe'), 'title' => __('Subscribe'))),
          "subscribe/{$group['Group']['name']}", array('escape' => false));
      } else {
        $actions[] = $this->Html->link(
          $this->Html->image('icons/group_delete.png', array('alt' => __('Unsubscribe'), 'title' => __('Unsubscribe'))),
          "unsubscribe/{$group['Group']['name']}", array('escape' => false));
      }
    }

    if (in_array($group['Group']['id'], $myGroupIds) || $isAdmin) {
      $actions[] = $this->Html->link(
        $this->Html->image('icons/pencil.png', array('alt' => __('Edit'), 'title' => __('Edit'))),
        "edit/{$group['Group']['name']}", array('escape' => false));
      $delConfirm = __("Do you realy want to delete the group '%s'?", $group['Group']['name']);
      $actions[] = $this->Html->link(
        $this->Html->image('icons/delete.png', array('alt' => __('Delete'), 'title' => __('Delete'))),
        'delete/'.$group['Group']['id'], array('escape' => false), $delConfirm);
    }
    $row = array(
      $this->Html->link($group['Group']['name'], "view/{$group['Group']['name']}", array('title' => $group['Group']['description'])),
      $this->Html->link($group['User']['username'], "/users/view/{$group['User']['username']}"),
      $this->Text->truncate($group['Group']['description'], 30, array('ending' => '...', 'exact' => false, 'html' => false)),
      count($group['Member']),
      $this->Html->tag('div', implode(' ', $actions), array('class' => 'actionlist'))
      );
    $cells[] = $row;
  }
  echo $this->Html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?>
</tbody>
</table>
<?php else: ?>
<div class="info">
<?php echo __('Currently no image groups are assigned. At the one hand each image could be assigned to a specific group. On the other hand a guest can be member of a set of groups. The guest is than able to view the images from his groups.'); ?>
</div>
<?php endif; ?>
