<?php
namespace Rails\ActiveRecord\Migration;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Metadata\Metadata;
use Rails;
use Rails\ActiveRecord\Schema;
// use Rails\ActiveRecord\Schema\SchemaMigration as Schema\SchemaMigration;

class Migrator
{
    protected $adapter;
    
    protected $filesDir;
    
    protected $migrationsTableName = 'schema_migrations';
    
    /**
     * @var Adapter $adapter
     * @var string $filesDir  Path to db-related folder (usually `$rails_root/db`)
     */
    public function __construct(Adapter $adapter, $filesDir)
    {
        $this->adapter  = $adapter;
        $this->filesDir = $filesDir;
    }
    
    /**
     * db:migrate
     */
    public function run()
    {
        $this->ensureMigrationsTableExists();
        Schema\SchemaMigration::setAdapter($this->adapter);
        Schema\SchemaMigration::setTableName($this->migrationsTableName);
        
        $pending = $this->getPendingMigrations();
        
        foreach ($pending as $version) {
            $this->runUp($version);
        }
    }
    
    public function runSeeds()
    {
        if ($this->getPendingMigrations()) {
            return;
        }
        $file = $this->filesDir . '/seeds.php';
        if (is_file($file)) {
            require $file;
        }
    }
    
    public function runUp($version)
    {
        $patt  = $this->migrationsDir() . '/' . $version . '_*.php';
        $files = glob($patt);
        
        if (!$files) {
            throw new Exception\RuntimeException(
                sprintf("Migration file for version %s not found", $version)
            );
        }
        
        $file = $files[0];
        require $file;
        
        $classes   = get_declared_classes();
        $className = array_pop($classes);
        unset($classes);
        
        $migrator = new $className($this->adapter);
        $migrator->up();
        
        Schema\SchemaMigration::create([
            'version' => $version
        ]);
    }
    
    public function loadSchema($schemaFile)
    {
        $importer = Schema\Migration\Importer();
        $importer->import($this->connection, $schemaFile);
        
    }
    
    protected function getPendingMigrations()
    {
        $ranVersions = Schema\SchemaMigration::all()->pluck('version');
        $availableVersions = $this->getAvailableMigrations();
        return array_diff($availableVersions, $ranVersions);
    }
    
    protected function ensureMigrationsTableExists()
    {
        $metadata = new Metadata($this->adapter);
        if (!in_array($this->migrationsTableName, $metadata->getTableNames())) {
            $schema = new Schema\Schema($this->adapter);
            $schema->createTable($this->migrationsTableName, ['id' => false], function($t) {
                $t->string('version');
            });
            $schema->addIndex($this->migrationsTableName, 'version', ['unique' => true]);
        }
    }
    
    protected function getAvailableMigrations()
    {
        $path  = $this->migrationsDir();
        $files = glob($path . '/*.php');
        $versions = [];
        foreach ($files as $file) {
            $file = pathinfo($file, PATHINFO_BASENAME);
            preg_match('/^(\d+)/', $file, $m);
            $versions[] = $m[1];
        }
        return $versions;
    }
    
    protected function migrationsDir()
    {
        return $this->filesDir . '/migrate';
    }
}
