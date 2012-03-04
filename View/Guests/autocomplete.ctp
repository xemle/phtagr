<ul>
<?php foreach($this->request->data as $group): ?>
  <li><?php echo $group['Group']['name']; ?></li>
<?php endforeach; ?>
</ul>
