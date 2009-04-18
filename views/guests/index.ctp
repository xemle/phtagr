<h1>Guests</h1>
<?php $session->flash(); ?>
<?php if (!empty($this->data)): ?>
<table class="default">
<thead>
  <tr>
    <td>Name</td>
    <td>Groups</td>
    <td>Actions</td>
  </tr>
</thead>

<tbody>
<?php $row=0; foreach($this->data as $guest): ?>
  <tr class="<?=($row++%2)?"even":"odd";?>">
    <td><?php echo $html->link($guest['Guest']['username'], 'edit/'.$guest['Guest']['id']); ?></td>
    <td><?=count($guest['Member']);?></td>
    <td><div class="actionlist"><?php 
      $delConfirm = "Do you realy want to delete the guest account '{$guest['Guest']['username']}'?";
      echo $html->link(
          $html->image('icons/pencil.png', array('alt' => 'Edit', 'title' => 'Edit')),
          'edit/'.$guest['Guest']['id'], null, false, false).' '.
        $html->link( 
          $html->image('icons/delete.png', array('alt' => 'Delete', 'title' => 'Delete')),
          'delete/'.$guest['Guest']['id'], null, $delConfirm, false); ?>
    </div></td>
  </tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<div class="info">
Currently, no guest accounts are set. You can create guests accounts to grant
access to your images e.g. to your friends or family. Please add also some
groups to the guest to grant access to this guest for these groups.
</div>
<?php endif; ?>

<?php
//debug($this->data);
?>
