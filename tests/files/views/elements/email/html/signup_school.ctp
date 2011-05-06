<p><?php printf(__('Dear %s,', true), $name); ?></p>
<p><?php printf(__('We\'d like to welcome you to %s.', true), $school); ?></p>
<p><?php
    $url = $this->Html->url(array('controller'=>'users', 'action'=>'login'), true);
    echo $this->Html->link(sprintf(__('Click here to login: %s', true), $url), $url);
?></p>
<p><?php echo $message; ?></p>
