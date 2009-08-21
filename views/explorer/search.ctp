<h1>Advanced Search</h1>

<?php echo $form->create(null, array('action' => 'query')); ?>
<fieldset><legend>Metadata</legend>
<?php 
  echo $form->input('Media.tags', array('after' => '<span class="hint">E.g. includeTag, -excludeTag</span>'));
  echo $form->input('Media.categories');
  echo $form->input('Media.locations');

  echo $form->input('Media.from', array('label' => 'Date from', 'after' => '<span class="hint">E.g. 2008-08-07</span>'));
  echo $form->input('Media.to', array('label' => 'Date to'));
?>
</fieldset>
<fieldset><legend>Options</legend>
<?php 
  $pages = array(6 => 6, 12 => 12, 24 => 24, 60 => 60, 120 => 120, 240 => 240);
  echo $form->input('Option.show', array('type' => 'select', 'options' => $pages, 'selected' => 12, 'label' => 'Page size'));

  $order = array('default' => 'Default', 'date' => 'Date', 'newest' => 'Newest', 'changes' => 'Changes', 'random' => 'Random', 'popularity' => 'Popularity');
  echo $form->input('Option.sort', array('type' => 'select', 'options' => $order, 'selected' => 'default', 'label' => 'Sort by'));
?>
</fieldset>
<?php if ($userRole >= ROLE_GUEST): ?>
<fieldset><legend>Advanced</legend>
<?php
  if ($userId) {
    echo $form->hidden('User.username', array('value' => $userId));
  }
  if ($userRole >= ROLE_GUEST) {
    echo $form->input('Media.name');
    $type = array('any' => 'Any Type', 'image' => 'Image', 'video' => 'Video');
    echo $form->input('Media.type', array('type' => 'select', 'options' => $type, 'selected' => 'any', 'label' => 'File Type'));
  }
  if ($userRole >= ROLE_USER) {
    if (!$userId) {
      echo $form->input('User.username');
    }
    echo $form->input('Group.name', array('type' => 'select', 'options' => $groups, 'selected' => -1, 'label' => 'Group'));

    $visibility = array('any' => 'Any', 'private' => 'Private', 'group' => 'Group members', 'user' => 'User', 'public' => 'Public');
    echo $form->input('Media.visibility', array('type' => 'select', 'options' => $visibility, 'label' => 'Media visibility'));
  }

  $op = array(0 => 'default', 'AND' => 'AND', 'OR' => 'OR');
  echo $form->input('Media.operand', array('type' => 'select', 'options' => $op, 'selected' => 'default', 'label' => 'General Operand'));
  echo $form->input('Media.tag_op', array('type' => 'select', 'options' => $op, 'selected' => 'default', 'label' => 'Tag operand'));
  echo $form->input('Media.category_op', array('type' => 'select', 'options' => $op, 'selected' => 'default', 'label' => 'Category operand'));
  echo $form->input('Media.location_op', array('type' => 'select', 'options' => $op, 'selected' => 'default', 'label' => 'Location operand'));

?>
</fieldset>
<?php endif; ?>
<?php echo $form->end('Search'); ?>
