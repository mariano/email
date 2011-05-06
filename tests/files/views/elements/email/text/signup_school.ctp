<?php
$this->viewVars['from'] = 'layout@email.com';
$this->viewVars['subject'] = sprintf(__('Welcome to %s', true), '${school}');
?>
<?php printf(__('Dear %s,', true)."\n", $name); ?>
<?php printf(__('We\'d like to welcome you to %s.', true)."\n", $school); ?>
<?php
    $url = $this->Html->url(array('controller'=>'users', 'action'=>'login'), true);
    printf(__('Click here to login: %s', true)."\n", $url);
?>
<?php echo $message; ?>
