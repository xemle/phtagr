<h1><?php printf(__(" User: %s", true), $this->data['User']['username']); ?></h1>

<?php echo $session->flash(); ?>


<?php if (isset($fsroots['path']['fsroot'])): ?>
<p><?php __('The user can import media files from following external directories:'); ?></p>

<table class="default">
<thead>
  <tr>
    <td><?php __('Directory'); ?></td>
    <td><?php __('Actions'); ?></td>
  </tr>
</thead>
<tbody>
<?php foreach($fsroots['path']['fsroot'] as $root): ?>
  <tr>
    <td><?php  echo "$root"; ?></td>
    <td><?php
      $delConfirm = sprintf(__("Do you really want to detete the path '%s' of '%s'?", true), $root, $this->data['User']['username']);
      echo $html->link($html->image('icons/delete.png', array('alt' => __('Delete', true), 'title' => sprintf(__("Delete path '%s'", true), $root))),
    '/admin/users/delpath/'.$this->data['User']['id'].'/'.$root, array('escape' => false), $delConfirm);?></td>
  </tr>
<?php endforeach; ?>
</tbody>
</table>

<?php else: ?>
<div class="info">
<p><?php __('Currently no external directories are set for this user.'); ?></p>

<p><?php __('The user has a dedicated upload directory which is handled by phTagr itself. External directories can be added here to the user to allow the import of external media files from the local file system.'); ?></p>
</div>
<?php endif; ?>

<?php echo $form->create(null, array('action' => 'path/'.$this->data['User']['id'])); ?>
<fieldset><legend><?php __('Add Directory'); ?></legend>
<?php echo $form->input('Option.path.fspath', array('label' => __('Directory', true))); ?>
</fieldset>
<?php echo $form->end(__('Add', true)); ?>
