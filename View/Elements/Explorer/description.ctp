<?php 
  $this->Search->initialize(); 
  echo $this->element('explorer/date', array('media' => $media));
  if (count($media['Tag'])) {
    echo $this->Html->tag('p', 
      __("Tags").' '.implode(', ', $this->ImageData->linkList('/explorer/tag', Set::extract('/Tag/name', $media))), 
      array('class' => 'tag list', 'escape' => false));
  }
  if (count($media['Category'])) {
    echo $this->Html->tag('p',
      __("Categories").' '.implode(', ', $this->ImageData->linkList('/explorer/category', Set::extract('/Category/name', $media))),
      array('class' => 'category list', 'escape' => false));
  }
  if (count($media['Location'])) {
    echo $this->Html->tag('p',
      __("Locations").' '.implode(', ', $this->ImageData->linkList('/explorer/location', Set::extract('/Location/name', $media))),
      array('class' => 'location list', 'escape' => false));
  }
  if ($this->Search->getUser() == $currentUser['User']['username'] && !empty($media['Group']['name'])) {
    echo $this->Html->tag('p',
      __("Group").' '.implode(', ', $this->ImageData->linkList('/explorer/group', Set::extract('/Group/name', $media))),
      array('class' => 'group list', 'escape' => false));
  }
  if ($this->Search->getUser() == $currentUser['User']['username'] && $currentUser['User']['role'] >= ROLE_USER) {
    echo $this->Html->tag('p',
      __("Access").' '
        .$this->ImageData->_acl2icon($media['Media']['gacl'], __('Group members')) . ', '
        .$this->ImageData->_acl2icon($media['Media']['uacl'], __('Users')) . ', '
        .$this->ImageData->_acl2icon($media['Media']['oacl'], __('All')),
      array('class' => 'access list', 'escape' => false));
    }
?>