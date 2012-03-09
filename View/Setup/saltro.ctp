<h1><?php echo __("Security settings"); ?></h1>

<?php echo $this->Session->flash(); ?>

<p><?php echo __("phTagr could not set the security salt because the a configuration file file is write protected.  Please change the configuration manually"); ?></p>

<p><?php echo __("Change the <b>Security.salt</b> setting in the core configuration file <code>%s</code> (line %d) to a random string with alpha numeric values. You can change the setting to the provided value below. After changing, click on %s.", h($file), $line, $this->Html->link(__('continue'), 'saltro')); ?><p>

<p><?php echo __("Current security salt value"); ?></p>
<pre>Configure::write('Security.salt', '<?php echo h($oldSalt); ?>');</pre>

<p><?php echo __("New security salt value"); ?></p>
<pre>Configure::write('Security.salt', '<?php echo h($salt); ?>');</pre>

<p></p>
<?php echo $this->Html->link(__('Continue'), 'saltro', array('class' => 'button')); ?>
<?php
  $script = <<<SCRIPT
(function($) {
  $(document).ready(function() {
    $('.button').button();
  });
})(jQuery);
SCRIPT;
  echo $this->Html->scriptBlock($script, array('inline' => false));
?>
