<?php 
  if (isset($mainMenuExplorer))
    echo $explorerMenu->getMainMenu($mainMenuExplorer);
  elseif (isset($mainMenu))
    echo $menu->getMainMenu($mainMenu);
?>
