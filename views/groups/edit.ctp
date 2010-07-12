<h1><?php printf(__('Group: %s', true), $this->data['Group']['name']); ?></h1>

<?php echo $form->create(null, array("action" => "edit/".$this->data['Group']['id'])); ?>
<fieldset><legend>Group Settings</legend>

<?php
  echo $form->hidden('Group.id');
  echo $form->input('Group.name');

  echo $form->input('Group.description', array('type' => 'textbox'));

  $typeOptions = array(
    GROUP_TYPE_PUBLIC => 'Public',
    GROUP_TYPE_HIDDEN => 'Hidden'
    );
  $accessOptions = array(
    GROUP_ACCESS_MEMBER => 'Group Member',
    GROUP_ACCESS_REGISTERED => 'Registered',
    GROUP_ACCESS_ANONYMOUS => 'Anonymous'
    );
  $mediaViewOptions = array(
    GROUP_MEDIAVIEW_VIEW => 'View',
    GROUP_MEDIAVIEW_FULL => 'Full'
    );
  $taggingOptions = array(
    GROUP_TAGGING_READONLY => 'Read Only',
    GROUP_TAGGING_ONLYADD => 'Only Add',
    GROUP_TAGGING_FULL => 'Full'
    );

  echo $form->input('Group.type', array('type' => 'select', 'options' => $typeOptions, 'label' => 'Group Type'));
  echo $form->input('Group.access', array('type' => 'select', 'options' => $accessOptions, 'label' => 'Access'));
  echo $form->input('Group.media_view', array('type' => 'select', 'options' => $mediaViewOptions, 'label' => 'Media View'));
  echo $form->input('Group.tagging', array('type' => 'select', 'options' => $taggingOptions, 'label' => 'Tagging Options'));?>
</fieldset>
<?php echo $form->end("Apply Settings"); ?>

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
<?php echo $ajax->autocomplete('Member.username', '/groups/autocomplete'); ?></div>
</fieldset>
<?php echo $form->end(__('Add', true)); ?>

<?php echo $html->link(__('Show all groups', true), '/groups/index'); ?>
