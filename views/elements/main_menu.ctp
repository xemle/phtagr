<?php 
  $controller = $this->params['controller'];
  if ($controller == 'explorer') {
    echo $explorerMenu->getMainMenu();
  } elseif (isset($mainMenu)) {
    echo $menu->getMainMenu($mainMenu);
  }
?>
