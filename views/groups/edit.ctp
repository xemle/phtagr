<h1>Group: <?php echo $this->data['Group']['name']; ?></h1>
<?php $session->flash() ?>

<?php if(count($this->data['Member'])): ?>
<h2>Member List</h2>
<table class="default">
<thead>
  <tr>
    <td>Member</td>
    <td>Actions</td>
  <tr>
</thead>

<tbody>
<?php $row=0; foreach($this->data['Member'] as $member): ?>
  <tr class="<?=($row++%2)?"even":"odd";?>">
    <td><?php 
      if ($member['creator_id'] == $this->data['User']['id'])
        echo $html->link($member['username'], '/guests/edit/'.$member['id']);
      else 
        echo $member['username']; ?></td>
    <td><div class="actionlist"><?php
      $delConfirm = "Do you really want to delete the member '{$member['username']}' from this group '{$this->data['Group']['name']}'?";
      echo $html->link( 
        $html->image('icons/delete.png', array('alt' => 'Delete', 'title' => 'Delete')), 
        '/groups/deleteMember/'.$this->data['Group']['id'].'/'.$member['id'], null, $delConfirm, false); ?>
    </div></td>
  </tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<div class="info">Currently this group has no members. Please add users to grant access to your private images.</div>
<?php endif; ?>

<?php echo $form->create(null, array('action' => 'addMember/'.$this->data['Group']['id']));?>

<fieldset><legend>Add member</legend>
<div class="input"><label>Group</label>
<?php echo $ajax->autocomplete('User/username', '/groups/autocomplete'); ?></div>
</fieldset>
<?php echo $form->submit('Add'); ?>
</form>

<?php 
echo $html->link('List all groups', '/groups/index');
?>
<?php debug($this->data); ?>
