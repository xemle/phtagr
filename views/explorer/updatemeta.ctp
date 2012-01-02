<?php 
  $search->initialize();
  $pos = $search->getPos();
  $index = 0;
  echo $this->element('explorer/media', array('media' => $this->data, 'index' => $index, 'pos' => $pos)); 
?>
