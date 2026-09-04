<?php
/**
 * @var \App\View\AppView $this
 * @var string $message
 * @var int $code
 */
$code = $code ?? 400;
$heading = match ($code) {
    404 => 'Not found',
    405 => 'Method not allowed',
    default => 'Bad request',
};
$this->layout = 'error';
$this->assign('title', $message);
?>
<h1><?= h((string)$code) ?> — <?= h($heading) ?></h1>
<div class="card bad"><p><?= h($message) ?></p></div>
