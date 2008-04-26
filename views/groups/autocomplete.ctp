<?php debug($this->data); ?>
<ul>
<?php foreach($this->data as $guest): ?>
  <li><?php echo $guest['Guest']['username']; ?></li>
<?php endforeach; ?>
</ul>
