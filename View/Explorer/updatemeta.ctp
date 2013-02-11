<?php
  $this->Search->initialize();
  $pos = $this->Search->getPos();
  $index = 0;

  $view = $this->Search->getView();
  if ($view == 'small') {
    $element = "Explorer/media_small";
  } else if ($view == 'compact' ) {
    $element = "Explorer/media_compact";
  } else {
    $element = "Explorer/media";
  }
  echo $this->element($element, array('media' => $this->request->data, 'index' => $index, 'pos' => $pos));
?>
