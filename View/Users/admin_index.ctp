<h1>Users</h1>
<?php echo $this->Session->flash(); ?>

<?php $this->Paginator->options(array('update' => 'main_content', 'indicator' => 'spinner'));?>

<?php echo $this->Paginator->prev(__('Prev'), null, null, array('class' => 'disabled')); ?>
<?php echo " | "; echo $this->Paginator->numbers(); ?>
<?php echo $this->Paginator->next(__('Next'), null, null, array('class' => 'disabled')); ?>

Page <?php echo $this->Paginator->counter() ?>

<table class="default">
<thead>
<tr>
  <td><?php echo $this->Paginator->sort(__('username'), 'Username'); ?></td>
  <td><?php echo $this->Paginator->sort(__('firstname'), 'Firstname'); ?></td>
  <td><?php echo $this->Paginator->sort(__('lastname'), 'Lastname'); ?></td>
  <td><?php echo __('nr.files'); ?></td>
  <td><?php echo __('nr.media'); ?></td>
  <td><?php echo $this->Paginator->sort(__('quota'), 'User quota'); ?></td>
  <td><?php echo __('size internal'); ?></td>
  <td><?php echo __('size external'); ?></td>
  <td><?php echo __('Guests'); ?></td>
  <td><?php echo $this->Paginator->sort(__('User role'), 'role'); ?></td>
  <td><?php echo __('Actions'); ?></td>
</tr>
</thead>

<tbody>
<?php $row=0; foreach($this->request->data as $user): ?>
<?php if (isset($user['User']['id'])) { ?>
<?php $userId = $user['User']['id']; ?>
<tr class="<?php echo ($row++%2)?"even":"odd";?>">
  <td><?php echo $this->Html->link($user['User']['username'], '/admin/users/edit/'.$user['User']['id']);?></td>
  <td><?php echo $user['User']['firstname'];?></td>
  <td><?php echo $user['User']['lastname'];?></td>
  <td><?php echo $this->request->data['calc'][$userId]['FileCount'];?></td>
  <td><?php echo $this->request->data['calc'][$userId]['MediaCount'];?></td>
  <td><?php echo $this->Number->toReadableSize($user['User']['quota']);?></td>
  <td><?php echo $this->Number->toReadableSize($this->request->data['calc'][$userId]['file.size.internal']);?></td>
  <td><?php echo $this->Number->toReadableSize($this->request->data['calc'][$userId]['file.size.external']);?></td>


    
  <td><?php  echo count($user['Guest']); ?></td>
  <td><?php
  switch ($user['User']['role']) {
    case ROLE_ADMIN:  echo __('Admin'); break;
    case ROLE_SYSOP:  echo __('SysOp'); break;
    case ROLE_USER:  echo __('Member'); break;
    case ROLE_GUEST:  echo __('Guest'); break;
    case ROLE_NOBODY: echo __('Nobody'); break;
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
<?php } ?>
<?php endforeach; ?>
</tbody>
</table>
