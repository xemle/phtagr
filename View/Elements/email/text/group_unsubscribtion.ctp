Hello <?php echo $group['User']['username']; ?>!

<?php echo $user['User']['username']; ?> unsubscribed from your group <?php echo $group['Group']['name']; ?>. 

To see more information about <?php echo $user['User']['username']; ?>, please vvisit <?php echo Router::url("/users/view/{$user['User']['username']}", true); ?>.

Group info:

Description: <?php echo $group['Group']['description']; ?> 
Number of members: <?php echo count($group['Member']); ?> 

More details of your group <?php echo $group['Group']['name']; ?> are available at <?php echo Router::url("/groups/view/{$group['Group']['name']}", true); ?>.


Sincerely

Your phTagr Agent
