<h1>Groups</h1>
<?php if ($session->check('Message.flash')) $session->flash(); ?>

<?php if (!empty($this->data)): ?>
<table class="default">
<thead>
<?php 
  $headers = array(__("Name", true), __("Description", true), __("Type", true), __("Access", true), __("Media View", true), __("Tagging", true), __("Members", true), __("Actions", true));
  echo $html->tableHeaders($headers);
?>
</thead>

<tbody>
<?php 
  $cells = array();

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

  foreach($this->data as $group) {

    $delConfirm = h("Do you realy want to delete the group {$group['Group']['name']}?");

    $actions = '<div class="actionlist">';
    $actions .= $html->link(
        $html->image('icons/pencil.png', array('alt' => 'Edit', 'title' => 'Edit')),
          'edit/'.$group['Group']['id'], null, false, false).' '.
        $html->link( 
          $html->image('icons/delete.png', array('alt' => 'Delete', 'title' => 'Delete')),
          'delete/'.$group['Group']['id'], null, $delConfirm, false);
    $actions .= '</div>';

    $cells[] = array(
      $html->link($group['Group']['name'], 'edit/'.$group['Group']['id'], array('title' => h($group['Group']['description']))),
      $text->truncate($group['Group']['description'], 32, '...', false),
      $typeOptions[$group['Group']['type']],
      $accessOptions[$group['Group']['access']],
      $mediaViewOptions[$group['Group']['media_view']],
      $taggingOptions[$group['Group']['tagging']],
      count($group['Member']),
      $actions
      );
  }
  echo $html->tableCells($cells, array('class' => 'odd'),  array('class' => 'even'));
?>
</tbody>
</table>
<?php else: ?>
<div class="info">
Currently no image groups are assigned. At the one hand each image could be
assigned to a specific group. On the other hand a guest can be member of a set
of groups. The guest is than able to view the images from his groups. 
</div>
<?php endif; ?>
<?php
//debug($this->data);
?>
