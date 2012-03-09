<?php 
  $this->Search->initialize();
  $pos = $this->Search->getPos();
  $index = 0;
  echo $this->element('Explorer/media', array('media' => $this->request->data, 'index' => $index, 'pos' => $pos)); 
?>
