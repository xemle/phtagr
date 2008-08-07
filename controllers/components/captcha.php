<?php 
App::import('vendor', 'kcaptcha');
class CaptchaComponent extends Object
{
  function startup(&$controller) {
    $this->controller = $controller;
  }

  function render() {
    $kcaptcha = new KCAPTCHA();
    $this->controller->Session->write('captcha', $kcaptcha->getKeyString());
  }
}
?>
