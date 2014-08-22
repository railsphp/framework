<?php
echo $this->phpOpenTag();
echo $this->defineNamespace();
?>
class <?= $this->className ?> extends <?= $this->baseClass() ?>
{
    
}
