<h1>Users</h1>
<?php echo $this->Session->flash(); ?>

<?php $paginator->options(array('update' => 'main_content', 'indicator' => 'spinner'));?>

<?php echo $paginator->prev(__('Prev'), null, null, array('class' => 'disabled')); ?>
<?php echo " | "; echo $paginator->numbers(); ?>
<?php echo $paginator->next(__('Next'), null, null, array('class' => 'disabled')); ?>

Page <?php echo $paginator->counter() ?>

<table class="default">
<thead>
<tr>
  <td><?php echo $paginator->sort(__('Username'), 'username'); ?></td>
  <td><?php echo $paginator->sort(__('Firstname'), 'firstname'); ?></td>
  <td><?php echo $paginator->sort(__('Lastname'), 'lastname'); ?></td>
  <td><?php echo __('Guests'); ?></td>
  <td><?php echo $paginator->sort(__('User role'), 'role'); ?></td>
  <td><?php echo __('Actions'); ?></td>
</tr>
</thead>

<tbody>
<?php $row=0; foreach($this->data as $user): ?>
<tr class="<?php echo ($row++%2)?"even":"odd";?>">
  <td><?php echo $this->Html->link($user['User']['username'], '/admin/users/edit/'.$user['User']['id']);?></td>
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
      echo __('Unknown'); 
      Logger::error("Unkown role of user: ".$user['User']['role']);
      break;
  };?></td>
  <td><?php
    $delConfirm = __("Do you really want to detete the user '%s'? This action is irreversible! All the data of the users will be deleted!", $user['User']['username']);
echo $this->Html->link(
  $this->Html->image('icons/pencil.png', array('alt' => __('Edit'), 'title' => __('Edit'))), 
    '/admin/users/edit/'.$user['User']['id'], array('escape' => false)).' '.
  $this->Html->link($this->Html->image('icons/delete.png', array('alt' => __('Delete'), 'title' => __('Delete'))), 
    '/admin/users/del/'.$user['User']['id'], array('escape' => false), $delConfirm);?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
