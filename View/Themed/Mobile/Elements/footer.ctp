<p><?php
  echo __("%s Web Gallery %s - mobile version", '&copy; 2012', $this->Html->link('phTagr', 'http://www.phtagr.org'));
  echo ' ' . $this->Html->link(__('View standard version'), array('controller' => 'home', 'action' => 'index', 'mobile' => 'off'));
?></p>
