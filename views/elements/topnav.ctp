<?php 
  if ($session->check('User.id')) {
    $userId = $session->read('User.id');
    $role = $session->read('User.role');
    $name = $session->read('User.username');
    if ($role >= ROLE_SYSOP) {
      echo $html->link(__('System', true), "/admin/system/general")." | ";
    }
    if ($role >= ROLE_USER) {
      echo $html->link(__('Preferences', true), "/options/profile")." | ";
    }
    echo $html->link(__("Logout", true), "/users/logout"). " ($name)";
  } else {
    echo $html->link(__("Login", true), "/users/login");
    if ($option->get('user.register.enable', 0)) {
      echo ' | '.$html->link(__("Register", true), "/users/register");
    }
  }

  echo "\n<div class=\"searchBox\" ><div class=\"searchBoxSub\" >";
  echo $form->create(null, array('url' => array('controller' => 'explorer', 'action' => 'quicksearch'))); 
  echo $form->input('Media.quicksearch', array ('label' => false, 'div' => false));
  $icon = Router::url("/img/icons/zoom.png");
  echo "<input type=\"image\" src=\"$icon\" width=\"16\" height=\"16\" id=\"go\" alt=\"Search\" title=\"Search\" />";
  echo $form->end();
  echo "</div></div>\n";
?>
