<?php 
  $search->initialize(); 
  echo $this->element('explorer/date', array('media' => $media));
  if (count($media['Tag'])) {
    echo $this->Html->tag('p', 
      __("Tags", true).' '.implode(', ', $imageData->linkList('/explorer/tag', Set::extract('/Tag/name', $media))), 
      array('class' => 'tag list', 'escape' => false));
  }
  if (count($media['Category'])) {
    echo $this->Html->tag('p',
      __("Categories", true).' '.implode(', ', $imageData->linkList('/explorer/category', Set::extract('/Category/name', $media))),
      array('class' => 'category list', 'escape' => false));
  }
  if (count($media['Location'])) {
    echo $this->Html->tag('p',
      __("Locations", true).' '.implode(', ', $imageData->linkList('/explorer/location', Set::extract('/Location/name', $media))),
      array('class' => 'location list', 'escape' => false));
  }
  if ($search->getUser() == $currentUser['User']['username'] && !empty($media['Group']['name'])) {
    echo $this->Html->tag('p',
      __("Group", true).' '.implode(', ', $imageData->linkList('/explorer/group', Set::extract('/Group/name', $media))),
      array('class' => 'group list', 'escape' => false));
  }
  if ($search->getUser() == $currentUser['User']['username'] && $currentUser['User']['role'] >= ROLE_USER) {
    echo $this->Html->tag('p',
      __("Access", true).' '
        .$imageData->_acl2icon($media['Media']['gacl'], __('Group members', true)) . ', '
        .$imageData->_acl2icon($media['Media']['uacl'], __('Users', true)) . ', '
        .$imageData->_acl2icon($media['Media']['oacl'], __('All', true)),
      array('class' => 'access list', 'escape' => false));
    }
?>