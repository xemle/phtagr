<h1>Guest: <?php echo $this->data['Guest']['username']; ?></h1>
<?php $session->flash(); ?>

<?php echo $form->create(null, array('action' => 'edit/'.$this->data['Guest']['id']));?>
<fieldset><legend>Guest</legend>
<?php
  echo $form->input('Guest.email');
  echo $form->input('Guest.expires', array('type' => 'text'));
  echo $form->input('Guest.webdav', array('type' => 'checkbox', 'checked' => ($this->data['Guest']['quota']>0?'checked':''), 'label' => 'Enable WebDAV access'));
?>
</fieldset>
<fieldset><legend>Password</legend>
<?php
  echo $form->input('Guest.password');
  echo $form->input('Guest.confirm', array('type' => 'password'));
?>
</fieldset>
<fieldset><legend>Comments</legend>
<?php
  $options = array(
    0 => 'None',
    1 => 'Name',
    3 => 'Name and captcha' 
    );
  $select = $this->data['Comment']['auth'];
  echo '<div class="input select">';
  echo $form->label(null, 'Authentication');
  echo $form->select('Comment.auth', $options, $select, null, false);
  echo '</div>';
?>
</fieldset>
<? echo $form->submit('Save'); ?>
</form>

<?php if(count($this->data['Member'])): ?>
<h2>Group List</h2>
<table class="default">
<thead>
  <tr>
    <td>Group</td>
    <td>Actions</td>
  <tr>
</thead>

<tbody>
<?php $row=0; foreach($this->data['Member'] as $group): ?>
  <tr class="<?=($row++%2)?"even":"odd";?>">
    <td><?php 
      if ($group['user_id'] == $userId)
        echo $html->link($group['name'], '/groups/edit/'.$group['id']); 
      else
        echo $group['name']; ?></td>
    <td><div class="actionlist"><?php
      $delConfirm = "Do you really want to delete the group '{$group['name']}' from this guest '{$this->data['Guest']['username']}'?";
      echo $html->link( 
        $html->image('icons/delete.png', array('alt' => 'Delete', 'title' => 'Delete')), 
        '/guests/deleteGroup/'.$this->data['Guest']['id'].'/'.$group['id'], null, $delConfirm, false); ?>
    </div></td>
  </tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<div class="info">Currently this guest account has no assigned groups.
Please add groups to grant access to your personal images.</div>
<?php endif; ?>

<?php echo $form->create(null, array('action' => 'addGroup/'.$this->data['Guest']['id']));?>
<fieldset><legend>Group Assignements</legend>
<div class="input"><label>Group</label>
<?php echo $ajax->autocomplete('Group/name', '/guests/autocomplete'); ?></div>
</fieldset>
<?php echo $form->submit('Add Group'); ?>
</form>

