<?php
$this->viewVars['subject'] = 'You have been invited to '.$Event['name'];
?>
Hi <?php echo $Moderator['name']; ?>,

The event <?php echo $Event['name']; ?> was scheduled for <?php echo $Event['date']; ?>.

You can go to <?php echo $URL['view']; ?> to see the event.

Or to report attendance, go to <?php echo $URL['report']; ?>
