<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title><?= $this->contentFor('pageTitle') ?></title>
  <style>
  body { background-color: #fff; color: #333; }
  body, p, ol, ul, td {
    font-family: helvetica, verdana, arial, sans-serif;
    font-size:   13px;
    line-height: 18px;
  }

  pre {
    background-color: #eee;
    padding: 10px;
    font-size: 11px;
    overflow: auto;
  }
    
  pre.scroll {
    max-height:400px;
  }

  a { color: #000; }
  a:visited { color: #666; }
  a:hover { color: #fff; background-color:#000; }
    
  .hide {
    display:none;
  }
  </style>
</head>
<body>
  <?= $this->contents() ?>
</body>
<?= $this->partial('exception_scripts') ?>
</html>
