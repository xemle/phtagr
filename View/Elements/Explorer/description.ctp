<?php
  $this->Search->initialize();
  echo $this->element('Explorer/date', array('media' => $media));
  $fields = $this->ImageData->getMediaFields($media);
  if (count($fields['keyword'])) {
    echo $this->Html->tag('p',
      __("Tags").' '.implode(', ', $this->ImageData->linkList('/explorer/tag', $fields['keyword'])),
      array('class' => 'tag list', 'escape' => false));
  }
  if (count($fields['category'])) {
    echo $this->Html->tag('p',
      __("Categories").' '.implode(', ', $this->ImageData->linkList('/explorer/category', $fields['category'])),
      array('class' => 'category list', 'escape' => false));
  }
  if (count($fields['location']) || ($media['Media']['latitude'] && $media['Media']['longitude'])) {
    $links = $this->ImageData->linkList('/explorer/location', $fields['location']);
    if ($media['Media']['latitude'] && $media['Media']['longitude']) {
      $geo = sprintf('%.2f', abs($media['Media']['latitude']));
      $geo .= $media['Media']['latitude'] >= 0 ? 'N/' : 'S/';
      $geo .= sprintf('%.2f', abs($media['Media']['longitude']));
      $geo .= $media['Media']['longitude'] >= 0 ? 'E' : 'W';
      $links[] = $geo;
    }

    echo $this->Html->tag('p',
      __("Locations").' '.implode(', ', $links),
      array('class' => 'location list', 'escape' => false));
  }
  if ($currentUser['User']['role'] > ROLE_NOBODY) {
    $groups = array();
    $userGroupIds = Set::extract('/Group/id', $currentUser);
    $userGroupIds = am(Set::extract('/Member/id', $currentUser));
    foreach ($media['Group'] as $group) {
      if ($media['User']['id'] == $currentUser['User']['id'] ||
        in_array($group['id'], $userGroupIds) ||
        !$group['is_hidden']) {
        $groups[] = $group;
      }
    }
    if (count($groups)) {
      echo $this->Html->tag('p',
        __("Groups").' '.implode(', ', $this->ImageData->linkList('/explorer/group', Set::extract('/name', $groups))),
        array('class' => 'group list', 'escape' => false));
    }
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
