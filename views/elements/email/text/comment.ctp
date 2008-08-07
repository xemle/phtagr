Dear <?php echo $user['User']['username']."\n"; ?>

A new comment of image <?php echo $data['Image']['name']; ?> was submitted.

View image: <?php echo Router::url('/images/view/'.$data['Image']['id'], true)."\n"; ?>
Delete comment: <?php echo Router::url('/comments/delete/'.$data['Comment']['id'], true)."\n"; ?>


From: <?php echo $data['Comment']['name']."\n"; ?>
Date: <?php echo $data['Comment']['date']."\n"; ?>

<?php echo $data['Comment']['text']."\n"; ?>


Sincerely

Your phTagr Agent
