<?php
/**
 * @var \App\View\AppView $this
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->fetch('title') ?></title>
    <?= $this->Html->css('app') ?>
</head>
<body>
    <main class="page">
        <?= $this->fetch('content') ?>
    </main>
</body>
</html>
