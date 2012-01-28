<?php 
  $this->Search->initialize();
  $pos = $this->Search->getPos();
  $index = 0;
  echo $this->element('explorer/media', array('media' => $this->data, 'index' => $index, 'pos' => $pos)); 
?>
