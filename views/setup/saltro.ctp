<?php $session->flash(); ?>

<h1>Security</h1>

<p>phTagr could not set the security salt because the a configuration file file
is write protected.  Please change the configuration manually</p>

<p>Change the <b>Security.salt</b> setting in the core configuration file
<code><?php echo h($file); ?></code> (line <?php echo $line; ?>) to a random
string with alpha numeric values. You can change the setting to the provided
value below. After changing, click on <?php echo $html->link('continue',
'saltro'); ?>.<p>

<p>Current Setting:</p>
<pre>Configure::write('Security.salt', '<?php echo h($oldSalt); ?>');</pre>

<p>New Setting:</p>
<pre>Configure::write('Security.salt', '<?php echo h($salt); ?>');</pre>

<?php echo $html->link('Continue', 'saltro'); ?>
