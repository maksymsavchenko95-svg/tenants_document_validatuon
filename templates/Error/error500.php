<?php
/**
 * @var \App\View\AppView $this
 * @var string $message
 */
$this->layout = 'error';
$this->assign('title', 'An Internal Server Error Occurred');
?>
<h1>An Internal Server Error Occurred</h1>
<div class="card bad"><p><?= h($message) ?></p></div>
