<?php debug($this->data); ?>
<ul>
<?php foreach($this->data as $user): ?>
  <li><?php echo $user['User']['username']; ?></li>
<?php endforeach; ?>
</ul>
