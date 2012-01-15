<?php 
App::import('vendor', 'kcaptcha');
class CaptchaComponent extends Object
{
  function initialize(&$controller) {
    $this->controller = $controller;
  }

  function render($name = 'captcha') {
    $kcaptcha = new KCAPTCHA();
    $this->controller->Session->write($name, $kcaptcha->getKeyString());
  }
}
?>
