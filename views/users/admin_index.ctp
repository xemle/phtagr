<h1>Users</h1>
<?php echo $session->flash(); ?>

<?php $paginator->options(array('update' => 'main_content', 'indicator' => 'spinner'));?>

<?php echo $paginator->prev(__('<< Prev', true), null, null, array('class' => 'disabled')); ?>
<?php echo " | "; echo $paginator->numbers(); ?>
<?php echo $paginator->next(__('Next >>', true), null, null, array('class' => 'disabled')); ?>

Page <?php echo $paginator->counter() ?>

<table class="default">
<thead>
<tr>
  <td><?php echo $paginator->sort(__('Username', true), 'username'); ?></td>
  <td><?php echo $paginator->sort(__('Firstname', true), 'firstname'); ?></td>
  <td><?php echo $paginator->sort(__('Lastname', true), 'lastname'); ?></td>
  <td><?php __('Guests'); ?></td>
  <td><?php echo $paginator->sort(__('User role', true), 'role'); ?></td>
  <td><?php __('Actions'); ?></td>
</tr>
</thead>

<tbody>
<?php $row=0; foreach($this->data as $user): ?>
<tr class="<?php echo ($row++%2)?"even":"odd";?>">
  <td><?php echo $html->link($user['User']['username'], '/admin/users/edit/'.$user['User']['id']);?></td>
  <td><?php echo $user['User']['firstname'];?></td>
  <td><?php echo $user['User']['lastname'];?></td>
  <td><?php count($user['Guest']); ?></td>
  <td><?php 
  switch ($user['User']['role']) {
    case ROLE_ADMIN: __('Admin'); break;
    case ROLE_SYSOP: __('SysOp'); break;
    case ROLE_USER: __('Member'); break;
    case ROLE_GUEST: __('Guest'); break;
    case ROLE_NOBODY: __('Nobody'); break;
    default: 
      echo __('Unknown', true); 
      Logger::error("Unkown role of user: ".$user['User']['role']);
      break;
  };?></td>
  <td><?php
    $delConfirm = sprintf(__("Do you really want to detete the user '%s'? This action is irreversible! All the data of the users will be deleted!", true), $user['User']['username']);
echo $html->link(
  $html->image('icons/pencil.png', array('alt' => __('Edit', true), 'title' => __('Edit', true))), 
    '/admin/users/edit/'.$user['User']['id'], array('escape' => false)).' '.
  $html->link($html->image('icons/delete.png', array('alt' => __('Delete', true), 'title' => __('Delete', true))), 
    '/admin/users/del/'.$user['User']['id'], array('escape' => false), $delConfirm);?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
