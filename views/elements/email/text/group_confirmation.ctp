Hello <?php echo $user['User']['username']; ?>!

Your group subscription of group <?php echo $group['Group']['name']; ?> was accepted. 

Group information:

Description: <?php echo $group['Group']['description']; ?> 
Number of members: <?php echo count($group['Member']); ?> 

More details of group <?php echo $group['Group']['name']; ?> are available at <?php echo Router::url("/groups/view/{$group['Group']['name']}", true); ?>.


Sincerely

Your phTagr Agent
