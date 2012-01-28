Hello

A new comment on <?php echo $data['Media']['name']; ?> was submitted. 

From: <?php echo $data['Comment']['name']."\n"; ?>
Date: <?php echo $data['Comment']['date']."\n"; ?>
Link: <?php echo Router::url('/images/view/'.$data['Media']['id'], true)."\n"; ?>

<?php echo $data['Comment']['text']."\n"; ?>


Sincerely

Your phTagr Agent

PS: This mail is a comment notification of your previous comment(s) on this media.
