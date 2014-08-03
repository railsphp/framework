<?php
namespace Rails\Test;

use DirectoryIterator;
use PHPUnit_TextUI_TestRunner as TestRunner;
use Zend\Db\Adapter\Exception\RuntimeException as UnknownDbException;
use Rails\Application\Base as Application;
use Rails\Test\TestCase;
use Rails\ActiveRecord\Persistence\PersistedModel\PersistedModel;
use Rails\ActiveRecord\Schema\Schema;
use Rails\ActiveRecord\Schema\SchemaMigration;
use Rails\ActiveRecord\Migration\Migrator;
use Rails\ActiveRecord\Schema\Migration\Exporter;
use Rails\ActiveRecord\Base as ActiveRecordBase;
use Rails\FactoryGirl\FactoryGirl;

class TestApplication
{
    protected $application;
    
    public function __construct(Application $application)
    {
        $this->setUpEnvironment($application);
        $this->setUpDatabase($application);
        $this->application = $application;
        TestCase::setApplication($application);
        
        $factoryGirl = new FactoryGirl([
            'factoryPaths' => [
                $application->config()['paths']['root']->expand('test', 'factories')
            ]
        ]);
        
        TestCase::setFactoryGirl($factoryGirl);
    }
    
    public function run($className)
    {
        $rootPath = str_replace('\\', '/', $this->application->config()['paths']['root']);
        
        $reflection = new \ReflectionClass($className);
        
        $testRunner = new TestRunner();
        $testRunner->run($reflection);
    }
    
    protected function setUpEnvironment(Application $application)
    {
        $application->configLoader()->loadEnvironmentConfig('test');
        
        $testDirPath = $application->config()['paths']['root']->expand('test');
        $testDir     = new DirectoryIterator($testDirPath);
        $loader      = $application->getService('loader');
        
        foreach ($testDir as $file) {
            if (!$file->isDot() && $file->isDir()) {
                $loader->addPath($file->getRealPath());
            }
        }
    }
    
    protected function setUpDatabase(Application $application)
    {
        $connManager = $application->getService('defaultConnectionManager');
        $connName    = 'test';
        
        if ($connManager->connectionExists($connName)) {
            $connManager->setDefaultConnection($connName);
            
            $adapter = $connManager->getAdapter($connName);
            
            $params = $adapter->getDriver()->getConnection()->getConnectionParameters();
            $filesDir = $application->config()['paths']['root']->expand('db');
            
            $schema = new Schema($adapter);
            $schema->dropDatabase($params['database']);
            $schema->createDatabase($params['database']);
            
            # Reset connection.
            $connManager->removeAdapter($connName);
            $adapter = $connManager->getAdapter($connName);
            
            $migrator = new Migrator($adapter, $filesDir);
            $migrator->run();
            
            # Clear table data.
            ActiveRecordBase::clearMetadatas();
            ActiveRecordBase::clearModelSchemas();
            SchemaMigration::setAdapter($adapter);
            
            $fixturesFile = $application->config()['paths']['root']->expand('test', 'fixtures', 'fixtures.php');
            
            # TODO: require outside of scope.
            require $fixturesFile;
        }
    }
}
