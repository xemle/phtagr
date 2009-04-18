<h1>Synchronize Files</h1>

<?php if ($this->data['total'] > 0): ?>
<?php echo "Synchronized {$this->data['synced']} of unsynchronized {$this->data['total']} images ({$this->data['errors']} errors)"; ?>

<?php 
  if ($this->data['total']-$this->data['synced'] > 0)
    echo $html->link('Synchronize again', 'sync'); ?>
<?php else: ?>
<div class="info">All images are synchronized</div>
<?php endif; ?>
