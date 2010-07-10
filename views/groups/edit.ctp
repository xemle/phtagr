<h1><?php printf(__('Group: %s', true), $this->data['Group']['name']); ?></h1>

<?php echo $session->flash() ?>

<?php if(count($this->data['Member'])): ?>
<h2><?php __('Member List'); ?></h2>
<table class="default">
<thead>
  <tr>
    <td><?php __('Member'); ?></td>
    <td><?php __('Actions'); ?></td>
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
      $delConfirm = sprintf(__("Do you really want to delete the member '%s' from this group '%s'?", true), $member['username'], $this->data['Group']['name']);
      echo $html->link( 
        $html->image('icons/delete.png', array('alt' => 'Delete', 'title' => __('Delete', true))), 
        '/groups/deleteMember/'.$this->data['Group']['id'].'/'.$member['id'], null, $delConfirm, false); ?>
    </div></td>
  </tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<div class="info"><?php __('Currently this group has no members. Please add users to grant access to your private images.'); ?></div>
<?php endif; ?>

<?php echo $form->create(null, array('action' => 'addMember/'.$this->data['Group']['id']));?>

<fieldset><legend><?php __('Add member'); ?></legend>
<div class="input"><label><?php __('Group'); ?></label>
<?php echo $ajax->autocomplete('User.username', '/groups/autocomplete'); ?></div>
</fieldset>
<?php echo $form->end(__('Add', true)); ?>

<?php echo $html->link(__('Show all groups', true), '/groups/index'); ?>
