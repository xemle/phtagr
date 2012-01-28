<h1><?php __('Guests'); ?></h1>

<?php echo $session->flash(); ?>

<?php if (!empty($this->data)): ?>
<table class="default">
<thead>
  <tr>
    <td><?php __('Name'); ?></td>
    <td><?php __('Groups'); ?></td>
    <td><?php __('Actions'); ?></td>
  </tr>
</thead>

<tbody>
<?php $row=0; foreach($this->data as $guest): ?>
  <tr class="<?php echo ($row++%2)?"even":"odd";?>">
    <td><?php echo $html->link($guest['Guest']['username'], 'edit/'.$guest['Guest']['id']); ?></td>
    <td><?php echo count($guest['Member']);?></td>
    <td><div class="actionlist"><?php 
      $delConfirm = "Do you realy want to delete the guest account '{$guest['Guest']['username']}'?";
      echo $html->link(
          $html->image('icons/pencil.png', array('alt' => 'Edit', 'title' => __('Edit', true))),
          'edit/'.$guest['Guest']['id'], array('escape' => false)).' '.
        $html->link( 
          $html->image('icons/delete.png', array('alt' => 'Delete', 'title' => __('Delete', true))),
          'delete/'.$guest['Guest']['id'], array('escape' => false), $delConfirm); ?>
    </div></td>
  </tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<div class="info">
<?php __('Currently, no guest accounts are set. You can create guests accounts to grant access to your images e.g. to your friends or family. Please add also some groups to the guest to grant access to this guest for these groups.'); ?>
</div>
<?php endif; ?>

<?php
//debug($this->data);
?>
