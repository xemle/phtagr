<h1>Users</h1>
<?php echo $session->flash(); ?>

<?php $paginator->options(array('update' => 'main_content', 'indicator' => 'spinner'));?>

<?php echo $paginator->prev('<< Prev', null, null, array('class' => 'disabled')); ?>
<?php echo " | "; echo $paginator->numbers(); ?>
<?php echo $paginator->next('Next >>', null, null, array('class' => 'disabled')); ?>

Page <?php echo $paginator->counter() ?>

<table class="default">
<thead>
<?php 
  $headers = array();
  $headers[] = $paginator->sort('username');
  $headers[] = $paginator->sort('firstname');
  $headers[] = $paginator->sort('lastname');
  $headers[] = __('Quota', true);
  $headers[] = $paginator->sort('role');
  $headers[] = __('Actions', true);
  echo $html->tableHeaders($headers);
?>
</thead>

<tbody>
<?php 
  $cells = array();
  foreach($this->data as $user) {
    $row = array();
    $row[] = $html->link($user['User']['username'], '/admin/users/edit/'.$user['User']['id']);
    $row[] = $user['User']['firstname'];
    $row[] = $user['User']['lastname'];
    $row[] = sprintf(__("%d%% of %s", true), sprintf('%.2f', 100 - 100 * $user['User']['free'] / max(0.1, $user['User']['quota'])), $number->toReadableSize($user['User']['quota']));
    switch ($user['User']['role']) {
      case ROLE_ADMIN: $row[] = __('Admin', true); break;
      case ROLE_SYSOP: $row[] = __('SysOp', true); break;
      case ROLE_USER: $row[] = __('Member', true); break;
      default: 
        $row[] = '';
        Logger::error("Invalid role of user: " . $user['User']['role']);
        break;
    }

    $actions = array();
    $actions[] = $html->link(
      $html->image('icons/pencil.png', array('alt' => __('Edit', true), 'title' => __('Edit', true))),
      '/admin/users/edit/'.$user['User']['id'], array('escape' => false));

    $delConfirm = sprintf(__("Do you really want to detete the user %s? This action is irreversible! All the data of the users will be deleted!", true), $user['User']['username']);
    $actions[] = $html->tag('span', $html->link(__('delete', true), 
      '/admin/users/del/'.$user['User']['id'], null, $delConfirm, false), array('class' => 'delete'));
    $row[] = implode(' ', $actions);
    $cells[] = $row;
  }
  echo $html->tableCells($cells, 'odd', 'even');
?>
</tbody>
</table>
