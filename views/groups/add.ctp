<?php $session->flash(); ?>

<?php echo $form->create(null, array('action' => 'add')); ?>

<fieldset><legend>Create new group</legend>
<table class="formular">
<?php
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
  echo $form->input('Group.tagging', array('type' => 'select', 'options' => $taggingOptions, 'label' => 'Tagging Options'));

?>
</table>

</fieldset>
<?php echo $form->end(__('Create', true)); ?>
