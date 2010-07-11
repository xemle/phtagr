<h1>User: <?=$this->data['User']['username']?></h1>

<?php echo $session->flash(); ?>


<?php if (isset($fsroots['path']['fsroot'])): ?>
<p>The user can import media files from following external directories:</p>

<table class="default">
<thead>
  <tr>
    <td>Directory</td>
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
    '/admin/users/delpath/'.$this->data['User']['id'].'/'.$root, array('escape' => false), $delConfirm);?></td>
  </tr>
<?php endforeach; ?>
</tbody>
</table>

<?php else: ?>
<div class="info">
<p>Currently no external directories are set for this user.</p>

<p>The user has a dedicated upload directory which is handled by phTagr itself.
External directories can be added here to the user to allow the import of external
media files from the local file system.</p>
</div>
<?php endif; ?>

<?php echo $form->create(null, array('action' => 'path/'.$this->data['User']['id'])); ?>
<fieldset><legend>Add Directory</legend>
<? echo $form->input('Option.path.fspath', array('label' => 'Directory')); ?>
</fieldset>
<?php echo $form->submit('Add'); ?>
</form>
