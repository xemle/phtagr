<?php 
  $controller = $this->params['controller'];
  $action = $this->params['action'];
  $items = array();
  $items[] = array(
    'text' => __('Home', true), 
    'link' => '/', 
    'type' => ($controller == 'home'?'current':''));
  if ($session->check('User.id')) {
    $userId = $session->read('User.id');
    $role = $session->read('User.role');
    $myImages = false;
    if (isset($search) && 
      ($controller == 'explorer' || $controller == 'images') && 
      $search->getUser() == $session->read('User.username')) {
      $myImages = true;
    }
    $items[] = array(
      'text' => __('Explorer', true), 
      'link' => '/explorer', 
      'type' => ($controller == 'explorer' && !$myImages?'current':''));

    if ($role >= ROLE_GUEST) {
      $items[] = array('text' => 
        __('My Photos', true), 
        'link' => "/explorer/user/".$session->read('User.username'), 
        'type' => ($controller == 'explorer' && $myImages?'current':''));
    }
    if ($role >= ROLE_USER)
      $items[] = array(
        'text' => __('My Files', true), 
        'link' => '/browser', 
        'type' => ($controller == 'browser'?'current':''));
  } else {
    $items[] = array(
      'text' => __('Explorer', true), 
      'link' => '/explorer', 
      'type' => ($controller == 'explorer'?'current':''));
  }

  echo $menu->getMainMenu(array('id' => false, 'title' => false, 'items' => $items));
?>
