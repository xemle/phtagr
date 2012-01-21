<h1><?php __("Security settings"); ?></h1>

<?php echo $session->flash(); ?>

<p><?php __("phTagr could not set the security salt because the a configuration file file is write protected.  Please change the configuration manually"); ?></p>

<p><?php printf(__("Change the <b>Security.salt</b> setting in the core configuration file <code>%s</code> (line %d) to a random string with alpha numeric values. You can change the setting to the provided value below. After changing, click on %s.", true), h($file), $line, $html->link(__('continue', true), 'saltro')); ?><p>

<p><?php __("Current security salt value"); ?></p>
<pre>Configure::write('Security.salt', '<?php echo h($oldSalt); ?>');</pre>

<p><?php __("New security salt value"); ?></p>
<pre>Configure::write('Security.salt', '<?php echo h($salt); ?>');</pre>

<p></p>
<?php echo $html->link(__('Continue', true), 'saltro', array('class' => 'button')); ?>
<?php
  $script = <<<'JS'
(function($) {
  $(document).ready(function() {
    $('.button').button();
  });
})(jQuery);
JS;
  echo $this->Html->scriptBlock($script, array('inline' => false));
?>
