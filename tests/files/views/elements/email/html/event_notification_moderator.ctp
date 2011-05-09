<?php
$this->viewVars['subject'] = 'You have been invited to '.$Event['name'];
?>
<p>Hi <?php echo $Moderator['name']; ?>,</p>
<p>The event <a href="<?php echo $URL['view']; ?>"><?php echo $Event['name']; ?></a> was scheduled for <?php echo $Event['date']; ?>.</p>
<p><a href="<?php echo $URL['report']; ?>">Click here</a> to report attendance for the event.</p>
