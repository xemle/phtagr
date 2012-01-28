<h1><?php echo __(" User: %s", $this->data['User']['username']); ?></h1>

<?php echo $this->Session->flash(); ?>


<?php if (isset($fsroots['path']['fsroot'])): ?>
<p><?php echo __('The user can import media files from following external directories:'); ?></p>

<table class="default">
<thead>
  <tr>
    <td><?php echo __('Directory'); ?></td>
    <td><?php echo __('Actions'); ?></td>
  </tr>
</thead>
<tbody>
<?php foreach($fsroots['path']['fsroot'] as $root): ?>
  <tr>
    <td><?php  echo "$root"; ?></td>
    <td><?php
      $delConfirm = __("Do you really want to detete the path '%s' of '%s'?", $root, $this->data['User']['username']);
      echo $this->Html->link($this->Html->image('icons/delete.png', array('alt' => __('Delete'), 'title' => __("Delete path '%s'", $root))),
    '/admin/users/delpath/'.$this->data['User']['id'].'/'.$root, array('escape' => false), $delConfirm);?></td>
  </tr>
<?php endforeach; ?>
</tbody>
</table>

<?php else: ?>
<div class="info">
<p><?php echo __('Currently no external directories are set for this user.'); ?></p>

<p><?php echo __('The user has a dedicated upload directory which is handled by phTagr itself. External directories can be added here to the user to allow the import of external media files from the local file system.'); ?></p>
</div>
<?php endif; ?>

<?php echo $this->Form->create(null, array('action' => 'path/'.$this->data['User']['id'])); ?>
<fieldset><legend><?php echo __('Add Directory'); ?></legend>
<?php echo $this->Form->input('Option.path.fspath', array('label' => __('Directory'))); ?>
</fieldset>
<?php echo $this->Form->end(__('Add')); ?>
