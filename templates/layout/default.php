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
    <title><?= $this->fetch('title') ?: 'Document Validation' ?></title>
    <?= $this->Html->css('app') ?>
    <?= $this->fetch('css') ?>
</head>
<body>
    <main class="page">
        <?= $this->fetch('content') ?>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/vue@3.5.13/dist/vue.global.prod.js"></script>
    <?= $this->fetch('script') ?>
</body>
</html>
