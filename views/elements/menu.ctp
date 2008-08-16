<?php 
  $controller = $this->params['controller'];
  $action = $this->params['action'];
  $items = array();
  $items[] = array(
    'text' => 'Home', 
    'link' => '/', 
    'type' => ($controller == 'home'?'current':''));
  if ($session->check('User.id')) {
    $userId = $session->read('User.id');
    $role = $session->read('User.role');
    $myImages = false;
    if (isset($query)) {
      $query->initialize();
      $myImages = $query->get('myimage');
    }
    $items[] = array(
      'text' => 'Explorer', 
      'link' => '/explorer', 
      'type' => ($controller == 'explorer' && !$myImages?'current':''));

    if ($role>=ROLE_GUEST)
      $items[] = array('text' => 
        'My Images', 
        'link' => "/explorer/user/$userId", 
        'type' => ($controller == 'explorer' && $myImages?'current':''));
    if ($role>=ROLE_USER)
      $items[] = array(
        'text' => 'My Files', 
        'link' => '/browser', 
        'type' => ($controller == 'browser'?'current':''));
  } else {
    $items[] = array(
      'text' => 'Explorer', 
      'link' => '/explorer', 
      'type' => ($controller == 'explorer'?'current':''));
  }

  echo $menu->getMainMenu(array('id' => false, 'title' => false, 'items' => $items));
?>
