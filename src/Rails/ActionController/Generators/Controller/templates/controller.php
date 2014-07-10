<?php
echo $this->phpOpenTag();
echo $this->defineNamespace();
?>
class <?= $this->className ?>Controller extends <?= $this->baseClass() ?>
{
    
}
