<h1><?php __('New Group'); ?></h1>

<?php echo $session->flash(); ?>

<?php echo $form->create(null, array('action' => 'add')); ?>

<fieldset><legend><?php __('Create new group'); ?></legend>
<?php
  echo $form->input('Group.name', array('label' => __('Name', true)));
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
  echo $form->input('Group.tagging', array('type' => 'select', 'options' => $taggingOptions, 'label' => 'Tagging Options'));

?>
</fieldset>
<?php echo $form->end(__('Create', true)); ?>
