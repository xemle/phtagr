<h1><?php echo __('Guest: %s', $this->data['Guest']['username']); ?></h1>

<?php echo $this->Session->flash(); ?>

<?php echo $this->Form->create(null, array('action' => 'edit/'.$this->data['Guest']['id']));?>
<fieldset><legend><?php echo __('Guest'); ?></legend>
<?php
  echo $this->Form->input('Guest.email', array('label' => __('Email')));
  echo $this->Form->input('Guest.expires', array('type' => 'text', 'label' => __('Expires')));
  echo $this->Form->input('Guest.webdav', array('type' => 'checkbox', 'checked' => ($this->data['Guest']['quota'] > 0 ? 'checked' : ''), 'label' => __('Enable WebDAV access')));
?>
</fieldset>
<fieldset><legend><?php echo __('Password'); ?></legend>
<?php
  echo $this->Form->input('Guest.password', array('label' => __('Password')));
  echo $this->Form->input('Guest.confirm', array('type' => 'password', 'label' => __('Confirm')));
?>
</fieldset>
<fieldset><legend><?php echo __('Comments'); ?></legend>
<?php
  $options = array(
    0 => __('None'),
    1 => __('Name'),
    3 => __('Name and captcha') 
    );
  $select = $this->data['Comment']['auth'];
  echo '<div class="input select">';
  echo $this->Form->label(null, __('Authentication'));
  echo $this->Form->select('Comment.auth', $options, $select, array('empty' => false));
  echo '</div>';
?>
</fieldset>
<?php echo $this->Form->submit(__('Save')); ?>
</form>

<?php if(count($this->data['Member'])): ?>
<h2><?php echo __('Group List'); ?></h2>
<table class="default">
<thead>
<?php
  $headers = array(
    __('Group'),
    __('Actions')
  );
  echo $this->Html->tableHeaders($headers);
?>  
</thead>

<tbody>
<?php 
  $cells = array();
  foreach($this->data['Member'] as $group) {
    $delConfirm = __("Do you really want to delete the group '%s' from this guest '%s'?", $group['name'], $this->data['Guest']['username']);
    $cells[] = array(
      $this->Html->link($group['name'], '/groups/view/'.$group['name']),
      $this->Html->link( 
        $this->Html->image('icons/delete.png', array('alt' => 'Delete', 'title' => 'Delete')), 
        '/guests/deleteGroup/'.$this->data['Guest']['id'].'/'.$group['id'], array('escape' => false), $delConfirm)
    );
  }
  echo $this->Html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?>
</tbody>
</table>
<?php else: ?>
<div class="info"><?php echo __('Currently this guest account has no assigned groups. Please add groups to grant access to your personal images.'); ?></div>
<?php endif; ?>

<?php echo $this->Form->create(null, array('action' => 'addGroup/'.$this->data['Guest']['id']));?>
<fieldset><legend><?php echo __('Group Assignements'); ?></legend>
<div class="input"><label><?php echo __('Group'); ?></label>
<?php echo $ajax->autocomplete('Group.name', '/guests/autocomplete'); ?></div>
</fieldset>
<?php echo $this->Form->submit(__('Add Group')); ?>
</form>

