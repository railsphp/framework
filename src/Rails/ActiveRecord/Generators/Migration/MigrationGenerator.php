<?php
namespace Rails\ActiveRecord\Generators\Migration;

use Rails\Generator\Generator;

class MigrationGenerator extends Generator
{
    protected $migrationFileName;
    
    protected $migrationClassName;
    
    public function writeFile()
    {
        $name = str_replace('/', '', $this->arg('name'));
        
        
        $this->migrationFileName = trim(preg_replace_callback(
            '/[A-Z]/',
            function($m) {
                return '_' . strtolower($m[0]);
            },
            $name
        ), '_');
        
        $this->migrationClassName = ucfirst(preg_replace_callback(
            '/(_[a-z])/',
            function($m) {
                return strtoupper(substr($m[0], 1));
            },
            $this->migrationFileName
        ));
        
        $this->createFile();
    }
    
    protected function filePath()
    {
        return 'db/migrate/' . gmdate('YmdHis') . '_' . $this->migrationFileName . '.php';
    }
    
    protected function template()
    {
        return __DIR__ . '/templates/migration.php';
    }
    
    protected function configure()
    {
        $this
            ->setName('migration')
            ->setDescription('Generate migration')
            ->addArgument(
                'name',
                'required',
                'Migration name (e.g. add_column_to_table, CreateUsers)'
            );
    }
}
