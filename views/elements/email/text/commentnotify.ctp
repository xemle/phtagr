Hello

A new comment of image <?php echo $data['Image']['name']; ?> was submitted. 

From: <?php echo $data['Comment']['name']."\n"; ?>
Date: <?php echo $data['Comment']['date']."\n"; ?>
Link: <?php echo Router::url('/images/view/'.$data['Image']['id'], true)."\n"; ?>

<?php echo $data['Comment']['text']."\n"; ?>


Sincerely

Your phTagr Agent

PS: This mail is a comment notification of your previous comment(s) on the image.
