<ul>
<?php foreach($this->request->data as $name): ?>
  <li><?php echo h($name); ?></li>
<?php endforeach; ?>
</ul>
