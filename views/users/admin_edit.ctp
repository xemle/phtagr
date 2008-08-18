<h1>User: <?=$this->data['User']['username']?></h1>

<?php $session->flash(); ?>

<?php echo $form->create(null, array('action' => 'edit/'.$this->data['User']['id'])); ?>
<fieldset><legend>General</legend>
<?php
  echo $form->input('User.firstname');
  echo $form->input('User.lastname');
  echo $form->input('User.email');
  $roles = array(ROLE_USER => 'User', ROLE_SYSOP => 'System Operator');
  if ($allowAdminRole) {
    $roles[ROLE_ADMIN] = 'Admin';
  }
  echo $form->input('User.role', array('type' => 'select', 'options' => $roles, 'selected' => $this->data['User']['role']));
?>
</fieldset>

<fieldset><legend>Password</legend>
<?php
  echo $form->input('User.password');
  echo $form->input('User.confirm', array('type' => 'password'));
?>
</fieldset>

<fieldset><legend>Other</legend>
<?php
  echo $form->input('User.expires', array('type' => 'text'));
  echo $form->input('User.quota', array('type' => 'text', 'label' => 'WebDAV Quota', 'value' => $number->toReadableSize($this->data['User']['quota'])));
?>
</fieldset>

<fieldset><legend>System Path</legend>
<?php if (isset($fsroots['path']['fsroot'])): ?>
<table class="default">
<thead>
  <tr>
    <td>Path</td>
    <td>Actions</td>
  </tr>
</thead>
<tbody>
<?php foreach($fsroots['path']['fsroot'] as $root): ?>
  <tr>
    <td><?php  echo "$root"; ?></td>
    <td><?php
      $delConfirm = "Do you really want to detete the path '$root' of '{$this->data['User']['username']}'?";
      echo $html->link($html->image('icons/delete.png', array('alt' => 'Delete', 'title' => "Delete path '$root'")),
    '/admin/users/delfsroot/'.$this->data['User']['id'].'/'.$root, null, $delConfirm, false);?></td>
  </tr>
<?php endforeach; ?>
</tbody>
</table>

<?php else: ?>
<div class="info">
<p>Currently no path to the system are set!</p>

<p>Each user has a dedicated user direcotry which is handled by phTagr itself.
To add external files from the system, you can add file system paths to the
user</p>
</div>
<?php endif; ?>
<? echo $form->input('Preference.path.fspath', array('label' => 'System Path')); ?>
</fieldset>
<?php echo $form->submit('Save'); ?>
</form>
