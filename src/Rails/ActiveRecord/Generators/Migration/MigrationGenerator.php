<?php
namespace Rails\ActiveRecord\Generators\Migration;

use Rails\Generator\Generator;

class MigrationGenerator extends Generator
{
    protected $migrationClassName;
    
    protected $migrationTemplate;
    
    protected $tableName;
    
    public function setProperties()
    {
        $this->migrationTemplate = 'migration.php';
        $fileName = $this->arg('name');
        $inflector = \Rails::getService('inflector');
        
        $this->migrationClassName = $inflector->camelize($fileName);
        
        switch (true) {
            // case (preg_match('/^(add|remove)_.*_(?:to|from)_(.*)/', $fileName, $m)):
                // $this->migrationAction = $m[1];
                // $this->tableName = $inflector->pluralize($m[2]);
                // break;
            
            // case (is_int(strpos($fileName, 'join_table'))):
                // break;
            
            case (strpos($fileName, 'create_') === 0):
                $this->tableName = substr($fileName, 7);
                $this->migrationTemplate = 'create_table_migration.php';
                break;
        }
    }
    
    public function writeFile()
    {
        $this->createGenerator();
    }
    
    protected function filePath()
    {
        return 'db/migrate/' . gmdate('YmdHis') . '_' . $this->arg('name') . '.php';
    }
    
    protected function template()
    {
        return __DIR__ . '/templates/' . $this->migrationTemplate;
    }
    
    protected function configure()
    {
        $this
            ->setName('migration')
            ->setDescription('Generate migration')
            ->addArgument(
                'name',
                'required',
                'Migration name (e.g. create_users, add_column_to_table)'
            )
            ->addOption(
                'timestamps',
                't',
                'optional',
                'Create timestamp columns (created_at and updated_at)',
                true
            )
            ->addOption(
                'recoverable',
                'r',
                'optional',
                'Create recoverable column (deleted_at)'
            );
    }
}
