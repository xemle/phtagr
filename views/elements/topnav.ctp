<?php 
  if ($session->check('User.id')) {
    $userId = $session->read('User.id');
    $role = $session->read('User.role');
    $name = $session->read('User.username');
    if ($role >= ROLE_SYSOP) {
      echo $html->link('System', "/admin/users")." | ";
    }
    if ($role >= ROLE_USER) {
      echo $html->link('Preferences', "/options/profile")." | ";
    }
    echo $html->link(_("Logout"), "/users/logout"). " ($name)";
  } else {
    echo $html->link(_("Login"), "/users/login");
    if ($option->get('user.register.enable', 0)) {
      echo ' | '.$html->link(_("Register"), "/users/register");
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
