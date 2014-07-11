<?= $this->phpOpenTag() ?>
class <?= $this->migrationClassName ?> extends Rails\ActiveRecord\Migration\Base
{
    public function up()
    {
        $this->createTable('<?= $this->tableName ?>', function($t) {
            
<?php if ($this->opt('timestamps')) : ?>
            $t->timestamps();
<?php endif ?>
<?php if ($this->opt('recoverable')) : ?>
            $t->recoverable();
<?php endif ?>
        });
    }
}
