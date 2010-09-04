<h1><?php __('Groups'); ?></h1>

<?php echo $session->flash(); ?>

<?php if (!empty($this->data)): ?>
<table class="default">
<thead>
<?php
  $headers = array(
    __('Name', true), 
    __('From User', true), 
    __('Members', true), 
    __('Actions', true));
  echo $html->tableHeaders($headers);
?>
</thead>

<tbody>
<?php
  $cells = array();
  $myGroupIds = Set::extract("/Group/id", $currentUser);
  $memberIds = Set::extract("/Member/id", $currentUser);
  $isAdmin = $currentUser['User']['role'] >= ROLE_ADMIN;

  foreach($this->data as $group) {
    $actions = array();
    if (!in_array($group['Group']['id'], $memberIds)) {
      $actions[] = $html->link(
        $html->image('icons/group_add.png', array('alt' => __('Subscribe', true), 'title' => __('Subscribe', true))),
        "subscribe/{$group['Group']['name']}", array('escape' => false));
    } else {
      $actions[] = $html->link(
        $html->image('icons/group_delete.png', array('alt' => __('Unsubscribe', true), 'title' => __('Unsubscribe', true))),
        "unsubscribe/{$group['Group']['name']}", array('escape' => false));
    }

    if (in_array($group['Group']['id'], $myGroupIds) || $isAdmin) {
      $actions[] = $html->link(
        $html->image('icons/pencil.png', array('alt' => __('Edit', true), 'title' => __('Edit', true))),
        "edit/{$group['Group']['name']}", array('escape' => false));
      $delConfirm = sprintf(__("Do you realy want to delete the group '%s'?", true), $group['Group']['name']);
      $actions[] = $html->link(
        $html->image('icons/delete.png', array('alt' => __('Delete', true), 'title' => __('Delete', true))),
        'delete/'.$group['Group']['id'], array('escape' => false), $delConfirm);
    }
    $row = array(
      $html->link($group['Group']['name'], "view/{$group['Group']['name']}", array('title' => $group['Group']['description'])),
      $html->link($group['User']['username'], "/user/view/{$group['User']['username']}"),
      count($group['Member']),
      $html->tag('div', implode(' ', $actions), array('class' => 'actionlist'))
      );
    $cells[] = $row;
  }
  echo $html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?>
</tbody>
</table>
<?php else: ?>
<div class="info">
<?php __('Currently no image groups are assigned. At the one hand each image could be assigned to a specific group. On the other hand a guest can be member of a set of groups. The guest is than able to view the images from his groups.'); ?>
</div>
<?php endif; ?>
