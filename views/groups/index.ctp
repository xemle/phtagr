<h1><?php __('Groups'); ?></h1>

<?php echo $session->flash(); ?>

<?php if (!empty($this->data)): ?>
<table class="default">
<thead>
  <tr>
    <td><?php __('Name'); ?></td>
    <td><?php __('Members'); ?></td>
    <td><?php __('Actions'); ?></td>
  </tr>
</thead>

<tbody>
<?php $row=0; foreach($this->data as $group): ?>
  <tr class="<?=($row++%2)?"even":"odd";?>">
    <td><?php echo $html->link($group['Group']['name'], 'edit/'.$group['Group']['id']); ?></td>
    <td><?php echo count($group['Member']) ?></td>
    <td><div class="actionlist"><?php 
      $delConfirm = "Do you realy want to delete the group '{$group['Group']['name']}'?";
      echo $html->link(
          $html->image('icons/pencil.png', array('alt' => 'Edit', 'title' => 'Edit')),
          'edit/'.$group['Group']['id'], null, false, false).' '.
        $html->link( 
          $html->image('icons/delete.png', array('alt' => 'Delete', 'title' => 'Delete')),
          'delete/'.$group['Group']['id'], null, $delConfirm, false); ?>
    </div></td>
  </tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<div class="info">
<?php __('Currently no image groups are assigned. At the one hand each image could be assigned to a specific group. On the other hand a guest can be member of a set of groups. The guest is than able to view the images from his groups.'); ?>
</div>
<?php endif; ?>
