<?php debug($this->request->data); ?>
<ul>
<?php foreach($this->request->data as $user): ?>
  <li><?php echo $user['User']['username']; ?></li>
<?php endforeach; ?>
</ul>
