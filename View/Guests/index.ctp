<h1><?php echo __('Guests'); ?></h1>

<?php echo $this->Session->flash(); ?>

<?php if (!empty($this->request->data)): ?>
<table class="default">
<thead>
  <tr>
    <td><?php echo __('Name'); ?></td>
    <td><?php echo __('Groups'); ?></td>
    <td><?php echo __('Actions'); ?></td>
  </tr>
</thead>

<tbody>
<?php $row=0; foreach($this->request->data as $guest): ?>
  <tr class="<?php echo ($row++%2)?"even":"odd";?>">
    <td><?php echo $this->Html->link($guest['Guest']['username'], 'edit/'.$guest['Guest']['id']); ?></td>
    <td><?php echo count($guest['Member']);?></td>
    <td><div class="actionlist"><?php
      $delConfirm = "Do you realy want to delete the guest account '{$guest['Guest']['username']}'?";
      echo $this->Html->link(
          $this->Html->image('icons/pencil.png', array('alt' => 'Edit', 'title' => __('Edit'))),
          'edit/'.$guest['Guest']['id'], array('escape' => false)).' '.
        $this->Html->link(
          $this->Html->image('icons/delete.png', array('alt' => 'Delete', 'title' => __('Delete'))),
          'delete/'.$guest['Guest']['id'], array('escape' => false), $delConfirm); ?>
    </div></td>
  </tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<div class="info">
<?php echo __('Currently, no guest accounts are set. You can create guests accounts to grant access to your images e.g. to your friends or family. Please add also some groups to the guest to grant access to this guest for these groups.'); ?>
</div>
<?php endif; ?>

<?php
//debug($this->request->data);
?>
