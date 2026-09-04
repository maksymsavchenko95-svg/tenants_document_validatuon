<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, mixed> $initial
 */
$this->assign('title', $initial['tenant']['name'] . ' — rules');
?>
<p><a href="/tenants">&larr; All tenants</a></p>
<h1><?= h($initial['tenant']['name']) ?> — rules</h1>

<div id="app" v-cloak></div>

<script type="application/json" id="initial"><?= json_encode($initial, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php $this->start('script'); ?>
<?= $this->Html->script('api') ?>
<?= $this->Html->script('tenant-rules') ?>
<?php $this->end(); ?>
