<h1><?php echo __("Database connection"); ?></h1>

<?php echo $this->Session->flash(); ?>

<div class="warning">
<?php echo __("The configuration file for the database connection could not be written. Please create a database configuration file by your own!"); ?>
</div>

<p><?php echo __("Below a sample configuration file is shown. Please adapt your database credentials and continue."); ?></p>

<p><pre><?php echo $dbConfig; ?></pre></p>

<p><pre><code>&lt;?php

class DATABASE_CONFIG {

  /**
   * Default database connection configuration
   */
  var $default = array(
                  'datasource' =&gt; 'Database/Mysql',
                  'persistent' =&gt; true,
                  'host' =&gt; 'localhost',
                  'database' =&gt; 'phtagr',
                  'login' =&gt; 'phtagr',
                  'password' =&gt; 'phtagr',
                  'prefix' =&gt; 'pt_',
                  'encoding' =&gt; 'utf8'
                 );

  /**
   * Database connection configuration for test environment
   */
  var $test = array(
                  'datasource' =&gt; 'Database/Mysql',
                  'persistent' =&gt; true,
                  'host' =&gt; 'localhost',
                  'database' =&gt; 'phtagr',
                  'login' =&gt; 'phtagr',
                  'password' =&gt; 'phtagr',
                  'prefix' =&gt; 'pt_test_',
                  'encoding' =&gt; 'utf8'
                 );
}
</code></pre></p>

<?php echo $this->Html->link(__('Continue'), 'configro', array('class' => 'button')); ?>

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
