<?php 
  $controller = $this->params['controller'];
  if ($controller == 'explorer' || $controller == 'images') {
    echo $explorerMenu->getMainMenu();
  } elseif (isset($mainMenu)) {
    echo $menu->getMainMenu($mainMenu);
  }
?>
