<h1>Advance Search</h1>

<?php echo $form->create(null, array('action' => 'query')); ?>
<fieldset><legend>Metadata</legend>
<?php 
  echo $form->input('Image.tags', array('after' => '<span class="hint">E.g. includeTag, -excludeTag</span>'));
  $op = array('0' => 'AND', '1' => 'OR', '2' => 'FUZZY');
  echo $form->input('Image.tag_op', array('type' => 'select', 'options' => $op, 'selected' => 0, 'label' => 'Tag operand'));

  echo $form->input('Image.categories');
  echo $form->input('Image.category_op', array('type' => 'select', 'options' => $op, 'selected' => 0, 'label' => 'Category operand'));

  echo $form->input('Image.locations');

  echo $form->input('Image.date_from', array('after' => '<span class="hint">E.g. 2008-08-07</span>'));
  echo $form->input('Image.date_to');
?>
</fieldset>
<fieldset><legend>Output</legend>
<?php 
  $pages = array(4 => 4, 12 => 12, 24 => 24, 60 => 60, 120 => 120, 240 => 240);
  echo $form->input('Query.show', array('type' => 'select', 'options' => $pages, 'selected' => 12, 'label' => 'Page size'));

  $order = array('date' => 'Date', 'newest' => 'Newest', 'changes' => 'Changes', 'random' => 'Random');
  echo $form->input('Query.order', array('type' => 'select', 'options' => $order, 'selected' => 'date', 'label' => 'Ordered by'));
?>
</fieldset>
<?php if ($userRole >= ROLE_GUEST): ?>
<fieldset><legend>General</legend>
<?php 
  if ($userId) {
    echo $form->hidden('User.username', array('value' => $userId));
  }
  if ($userRole >= ROLE_GUEST) {
    echo $form->input('Image.filename');
    $type = array('any' => 'Any Type', 'image' => 'Image', 'video' => 'Video');
    echo $form->input('Image.file_type', array('type' => 'select', 'options' => $type, 'selected' => 'any', 'label' => 'File Type'));
  }
  if ($userRole >= ROLE_USER) {
    if (!$userId) {
      echo $form->input('User.username');
    }
    echo $form->input('Group.id', array('type' => 'select', 'options' => $groups, 'selected' => -1, 'label' => 'Group'));

    $visibility = array('any' => 'Any', 'private' => 'Private', 'group' => 'Group members', 'user' => 'User', 'public' => 'Public');
    echo $form->input('Image.visibility', array('type' => 'select', 'options' => $visibility, 'label' => 'Image visibility'));
  }
?>
</fieldset>
<?php endif; ?>
<?php echo $form->submit('Search'); ?>
<?php echo $form->end(); ?>
