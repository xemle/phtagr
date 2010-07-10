<h1>Users</h1>
<?php echo $session->flash(); ?>

<?php $paginator->options(array('update' => 'main_content', 'indicator' => 'spinner'));?>

<?php echo $paginator->prev('<< Prev', null, null, array('class' => 'disabled')); ?>
<?php echo " | "; echo $paginator->numbers(); ?>
<?php echo $paginator->next('Next >>', null, null, array('class' => 'disabled')); ?>

Page <?php echo $paginator->counter() ?>

<table class="default">
<thead>
<tr>
  <td><?=$paginator->sort('username'); ?></td>
  <td><?=$paginator->sort('firstname'); ?></td>
  <td><?=$paginator->sort('lastname'); ?></td>
  <td>Guests</td>
  <td><?=$paginator->sort('role'); ?></td>
  <td>Actions</td>
</tr>
</thead>

<tbody>
<?php $row=0; foreach($this->data as $user): ?>
<tr class="<?=($row++%2)?"even":"odd";?>">
  <td><?php echo $html->link($user['User']['username'], '/admin/users/edit/'.$user['User']['id']);?></td>
  <td><?=$user['User']['firstname'];?></td>
  <td><?=$user['User']['lastname'];?></td>
  <td><?=count($user['Guest']); ?></td>
  <td><?php 
  switch ($user['User']['role']) {
    case ROLE_ADMIN: echo 'Admin'; break;
    case ROLE_SYSOP: echo 'SysOp'; break;
    case ROLE_USER: echo 'Member'; break;
    case ROLE_GUEST: echo 'Guest'; break;
    case ROLE_NOBODY: echo 'Nobody'; break;
    default: 
      echo 'Unknown'; 
      Logger::error("Unkown role of user: ".$user['User']['role']);
      break;
  };?></td>
  <td><?php
    $delConfirm = "Do you really want to detete the user '{$user['User']['username']}'? This action is irreversible! All the data of the users will be deleted!";
echo $html->link(
  $html->image('icons/pencil.png', array('alt' => 'Edit', 'title' => 'Edit')), 
    '/admin/users/edit/'.$user['User']['id'], array('escape' => false)).' '.
  $html->link($html->image('icons/delete.png', array('alt' => 'Delete', 'title' => 'Delete')), 
    '/admin/users/del/'.$user['User']['id'], array('escape' => false), $delConfirm);?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
