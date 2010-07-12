<h1><?php __("Database connection"); ?></h1>

<?php echo $session->flash(); ?>

<div class="warning">
<?php __("The configuration file for the database connection could not be written. Please create a database configuration file by your own!"); ?>
</div>

<p><?php __("Below a sample configuration file is shown. Please adapt your database credentials and continue."); ?></p>

<p><pre><?php echo $dbConfig; ?></pre></p>

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

<p><?php echo $html->link(__('Continue', true), 'configro'); ?></p>
