<h1><?php printf(__('Guest: %s', true), $this->data['Guest']['username']); ?></h1>

<?php echo $session->flash(); ?>

<?php echo $form->create(null, array('action' => 'edit/'.$this->data['Guest']['id']));?>
<fieldset><legend><?php __('Guest'); ?></legend>
<?php
  echo $form->input('Guest.email', array('label' => __('Email', true)));
  echo $form->input('Guest.expires', array('type' => 'text', 'label' => __('Expires', true)));
  echo $form->input('Guest.webdav', array('type' => 'checkbox', 'checked' => ($this->data['Guest']['quota'] > 0 ? 'checked' : ''), 'label' => __('Enable WebDAV access', true)));
?>
</fieldset>
<fieldset><legend><?php __('Password'); ?></legend>
<?php
  echo $form->input('Guest.password', array('label' => __('Password', true)));
  echo $form->input('Guest.confirm', array('type' => 'password', 'label' => __('Confirm', true)));
?>
</fieldset>
<fieldset><legend><?php __('Comments'); ?></legend>
<?php
  $options = array(
    0 => __('None', true),
    1 => __('Name', true),
    3 => __('Name and captcha', true) 
    );
  $select = $this->data['Comment']['auth'];
  echo '<div class="input select">';
  echo $form->label(null, __('Authentication', true));
  echo $form->select('Comment.auth', $options, $select, null, false);
  echo '</div>';
?>
</fieldset>
<? echo $form->submit(__('Save', true)); ?>
</form>

<?php if(count($this->data['Member'])): ?>
<h2><?php __('Group List'); ?></h2>
<table class="default">
<thead>
<?php
  $headers = array(
    __('Group', true),
    __('Actions', true)
  );
  echo $html->tableHeaders($headers);
?>  
</thead>

<tbody>
<?php 
  $cells = array();
  foreach($this->data['Member'] as $group) {
    $delConfirm = sprintf(__("Do you really want to delete the group '%s' from this guest '%s'?", true), $group['name'], $this->data['Guest']['username']);
    $cells[] = array(
      $html->link($group['name'], '/groups/view/'.$group['name']),
      $html->link( 
        $html->image('icons/delete.png', array('alt' => 'Delete', 'title' => 'Delete')), 
        '/guests/deleteGroup/'.$this->data['Guest']['id'].'/'.$group['id'], array('escape' => false), $delConfirm)
    );
  }
  echo $html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?>
</tbody>
</table>
<?php else: ?>
<div class="info"><?php __('Currently this guest account has no assigned groups. Please add groups to grant access to your personal images.'); ?></div>
<?php endif; ?>

<?php echo $form->create(null, array('action' => 'addGroup/'.$this->data['Guest']['id']));?>
<fieldset><legend><?php __('Group Assignements'); ?></legend>
<div class="input"><label><?php __('Group'); ?></label>
<?php echo $ajax->autocomplete('Group.name', '/guests/autocomplete'); ?></div>
</fieldset>
<?php echo $form->submit(__('Add Group', true)); ?>
</form>

