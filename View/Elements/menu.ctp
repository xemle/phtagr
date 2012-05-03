<?php
  $controller = $this->params['controller'];
  $action = $this->params['action'];
  $items = array();
  $items[] = array(
    'text' => __('Home'),
    'link' => '/',
    'type' => ($controller == 'home'?'current':''));
  if ($this->Session->check('User.id')) {
    $userId = $this->Session->read('User.id');
    $role = $this->Session->read('User.role');
    $myImages = false;
    if (isset($this->Search) &&
      ($controller == 'explorer' || $controller == 'images') &&
      $this->Search->getUser() == $this->Session->read('User.username')) {
      $myImages = true;
    }
    $items[] = array(
      'text' => __('Explorer'),
      'link' => '/explorer',
      'type' => ($controller == 'explorer' && !$myImages?'current':''));

    if ($role >= ROLE_GUEST) {
      $items[] = array('text' =>
        __('My Photos'),
        'link' => "/explorer/user/".$this->Session->read('User.username'),
        'type' => ($controller == 'explorer' && $myImages?'current':''));
    }
    if ($role >= ROLE_USER) {
      if (!$this->Option->get('user.browser.full', 0)) {
        $items[] = array(
          'text' => __('Upload'),
          'link' => '/browser/quickupload',
          'type' => ($controller == 'browser' && $action == 'quickupload'?'current':''));
      } else {
        $items[] = array(
          'text' => __('My Files'),
          'link' => '/browser',
          'type' => ($controller == 'browser' && $action != 'quickupload'?'current':''));
      }
    }
  } else {
    $items[] = array(
      'text' => __('Explorer'),
      'link' => '/explorer',
      'type' => ($controller == 'explorer'?'current':''));
  }

  echo $this->Menu->getMainMenu(array('id' => false, 'title' => false, 'items' => $items));
?>
