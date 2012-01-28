Hi <?php echo $user['User']['username']; ?>!

New comment on your media #<?php echo $data['Media']['id'];?>: <?php echo $data['Media']['name']; ?> was submitted.

Author: <?php echo $data['Comment']['name']."\n"; ?>
Email:  <?php echo $data['Comment']['email']."\n"; ?>
URL:    <?php echo $data['Comment']['url']."\n"; ?>
Date:   <?php echo $data['Comment']['date']."\n"; ?>

<?php echo $data['Comment']['text']."\n"; ?>

You can see the comments on this media here: <?php echo Router::url('/images/view/'.$data['Media']['id'], true)."\n"; ?>

Delete it: <?php echo Router::url('/comments/delete/'.$data['Comment']['id'], true)."\n"; ?>


Sincerely

Your phTagr Agent
