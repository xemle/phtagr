<?php $session->flash(); ?>
<h1>Create Database Configuration</h1>

<div class="warning">
The configuration file for the database connection could not be written. Please create a database configuration file by your own!
</div>

<p>Below a sample configuration file is shown. Please adapt your database credentials and click <?php echo $html->link('Retry', 'configreadonly'); ?></p>

<p><pre><?php echo $config; ?></pre></p>

<p><pre><code>&lt;?php

class DATABASE_CONFIG {
  var $default = array(
                  'driver' =&gt; 'mysql',
                  'connect' =&gt; 'mysql_connect',
                  'persistent' =&gt; true,
                  'host' =&gt; 'localhost',
                  'login' =&gt; 'phtagr',
                  'password' =&gt; 'phtagr',
                  'database' =&gt; 'phtagr',
                  'encoding' =&gt; 'utf8',
                  'prefix' =&gt; ''
                 );
}
?&gt;</code></pre></p>

<p><?php echo $html->link('Retry', 'configreadonly'); ?> database connection.</p>
