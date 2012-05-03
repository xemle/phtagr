<?php
  $controller = $this->params['controller'];
  if ($controller == 'explorer' || $controller == 'images') {
    $paginateActions = array('category', 'date', 'edit', 'group', 'index', 'location', 'query', 'tag', 'user', 'view');
    if (in_array($this->action, $paginateActions)) {
      echo $explorerMenu->getMainMenu();
    } elseif (isset($mainMenu)) {
      echo $menu->getMainMenu($mainMenu);
    }
  } elseif (isset($mainMenu)) {
    echo $menu->getMainMenu($mainMenu);
  }
?>
