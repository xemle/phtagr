<h1>Broswer</h1>

<?php echo $form->create('Browser', array('action' => 'import')); ?>
<p>
<?php
  $action = 'index';
  echo $html->link('root', $action)." / ";
  if ($path != "/") {
    $paths = explode('/', trim($path, '/'));
    $cur = "";
    foreach ($paths as $p) {
      $cur .= '/'.$p;

      echo $html->link($p, $action.$cur)." / ";
    } 
  }
  echo $form->hidden('path', array('value' => $path));
?>
</p>

<ul id="files">
  <li><?=$html->image('icons/folder.png').' '.$form->checkbox('import][', array("value" => "$path", 'id' => 'id'.rand()))." (This folder)";?></li>
<?php foreach($dirs as $dir): ?>
  <li><?php
    echo $html->image('icons/folder.png').' '.
      $form->checkbox('import][', array("value" => "$path$dir", 'id' => 'id'.rand())).' '.
      $html->link($dir, "index/$path$dir"); ?></li>
<?php endforeach; ?>
<?php foreach($files as $file => $type): ?>
  <li><?php
    if ($type != 'unknown') {
      if ($type == 'video')
        $icon = 'film';
      else
        $icon = 'picture';
      echo $html->image("icons/$icon.png").' '.
        $form->checkbox('import][', array("value" => "$path$file", 'id' => 'id'.rand())).' '.$file;
    } else {
      echo $html->image('icons/page_white.png').' '.
        $form->checkbox('disable', array('disabled' => 'disabled')).$file;
    } ?></li>
<?php endforeach; ?>
</ul>
<?=$form->submit('Import');?>
</form>
<? debug($path); ?>
<? debug($dirs); ?>
<? debug($files); ?>
