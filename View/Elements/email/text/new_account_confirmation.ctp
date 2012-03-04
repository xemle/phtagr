Dear <?php echo $user['User']['username']; ?>!

Please confirm your account creation

  <?php echo Router::url('/users/confirm/'.$key, true)."\n"; ?>

or go to <?php echo Router::url('/users/confirm', true); ?> and enter the confirmation key

  <?php echo $key."\n"; ?>

to finalize your account.

Sincerely

Your phTagr Agent
