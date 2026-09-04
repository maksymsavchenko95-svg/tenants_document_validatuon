<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, mixed> $initial
 */
$this->assign('title', $initial['tenant']['name'] . ' — validate a document');
?>
<p><a href="/tenants">&larr; All tenants</a> &nbsp;·&nbsp; <a href="/tenants/<?= (int)$initial['tenant']['id'] ?>/rules">Edit rules</a></p>
<h1><?= h($initial['tenant']['name']) ?> — validate a document</h1>

<div id="app" v-cloak></div>

<script type="application/json" id="initial"><?= json_encode($initial, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php $this->start('script'); ?>
<?= $this->Html->script('api') ?>
<?= $this->Html->script('document-validation') ?>
<?php $this->end(); ?>
