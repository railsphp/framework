<?php $this->provide('pageTitle', $this->pageTitle($this->presenter()->object())) ?>

<h1><?= $this->present()->title() ?></h1>

<pre class="scroll"><?= $this->present()->message() ?></pre>

<?php
if ($this->skipInfo($this->exception)) {
    return;
}
?>

<code>Application's root: <?= $this->application->config()['paths']['root'] ?></code>

<h3>Trace</h3>

<pre>
<?= $this->present()->trace() ?>
</pre>

<a href="#" onclick="return toggleErrorContext()">Toggle error context</a>

<div id="error_context" style="display: none;">
  <pre class="scroll"><?= $this->present()->errorContext() ?></pre>
</div>
