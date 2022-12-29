<?php
namespace Kafoso\DoctrineFirebirdDriver\Test\Integration;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;
use Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase;
use Kafoso\DoctrineFirebirdDriver\Platforms\FirebirdInterbasePlatform;

abstract class AbstractIntegrationTest extends \PHPUnit_Framework_TestCase
{
    const DEFAULT_DATABASE_FILE_PATH = '/var/lib/firebird/2.5/data/music_library.fdb';
    const DEFAULT_DATABASE_USERNAME = 'SYSDBA';
    const DEFAULT_DATABASE_PASSWORD = '88fb9f307125cc397f70e59c749715e1';

    protected $_entityManager;
    protected $_platform;
    // protected $_firebirdVersion; // speichert die Version der aktuellen DB

    public function setUp()
    {
        try
        {
            /*
            $doctrineConfiguration = static::getSetUpDoctrineConfiguration();
            $configurationArray = static::getSetUpDoctrineConfigurationArray();

            static::installFirebirdDatabase($configurationArray);
            $this->_entityManager = static::createEntityManager($doctrineConfiguration, $configurationArray);
            $this->_platform = new FirebirdInterbasePlatform();
            */
            
            $doctrineConfiguration = static::getSetUpDoctrineConfiguration();
            $configurationArray = static::getSetUpDoctrineConfigurationArray();
            static::installFirebirdDatabase($configurationArray);
            $this->_entityManager = static::createEntityManager($doctrineConfiguration, $configurationArray);
            $this->_platform = new FirebirdInterbasePlatform;
            $v = $this->GetFirebirdVersion();
            $this->_platform->SetFBVersion($v);
        }
        catch(Exception $exception) 
        {
            $fehler = $exception->getMessage();
            exec("echo \"$fehler\" > /tmp/doctrineErr");
        }
    }

    public function tearDown()
    {
        if ($this->_entityManager) {
            $this->_entityManager->getConnection()->close();
        }
    }
    
    // Verbindung aufbauen und Version der Firebird-DB ermitteln
    /**
     * 
     * 
     * @return type
     */
    public function GetFirebirdVersion() {
            $connection = $this->_entityManager->getConnection();
            $sql = $this->_platform->getODSVersionSql();
            $resultSetFirebirdVersion = $connection->query($sql);
            $wert = $resultSetFirebirdVersion->fetchColumn();
            //$this->_firebirdVersion = $this->_platform->getFirebirdVersionFromODSVersion($wert);
            
            return $this->_platform->getFirebirdVersionFromODSVersion($wert);
    }

    /**
     * @return EntityManager
     */
    protected static function createEntityManager(Configuration $configuration, array $configurationArray)
    {
        $doctrineConnection = new Connection(
            $configurationArray,
            new FirebirdInterbase\Driver,
            $configuration
        );
        $doctrineConnection->setNestTransactionsWithSavepoints(true);
        return EntityManager::create($doctrineConnection, $configuration);
    }

    protected static function installFirebirdDatabase(array $configurationArray)
    {
        return; // Erzeugung einer lokalen DB nicht moeglich ohne Firebird installation, also verwenden wir test-installation
        
        if (file_exists($configurationArray['dbname'])) {
            unlink($configurationArray['dbname']); // Don't do this outside tests
        }

        exec("echo test1234567 > /tmp/aaa");
        
        $cmd = sprintf(
            "isql-fb -input %s 2>&1",
            escapeshellarg(ROOT_PATH . "/tests/resources/database_create.sql")
        );
        exec($cmd);

        // chmod($configurationArray['dbname'], 0777);

        $cmd = sprintf(
            "isql-fb %s -input %s -password %s -user %s",
            escapeshellarg($configurationArray['dbname']),
            escapeshellarg(ROOT_PATH . "/tests/resources/database_setup.sql"),
            escapeshellarg($configurationArray['password']),
            escapeshellarg($configurationArray['user'])
        );
        exec($cmd);
    }

    /**
     * @return string
     */
    protected static function statementArrayToText(array $statements)
    {
        $statements = array_filter($statements, function($statement){
            return is_string($statement);
        });
        if ($statements) {
            $indent = "    ";
            array_walk($statements, function(&$v) use ($indent){
                $v = $indent . $v;
            });
            return PHP_EOL . implode(PHP_EOL, $statements);
        }
        return "";
    }

    /**
     * @return Configuration
     */
    protected static function getSetUpDoctrineConfiguration()
    {
        $cache = new \Doctrine\Common\Cache\ArrayCache;
        $doctrineConfiguration = new Configuration;
        $driverImpl = $doctrineConfiguration->newDefaultAnnotationDriver([ROOT_PATH . '/tests/resources/Test/Entity'], false);
        $doctrineConfiguration->setMetadataDriverImpl($driverImpl);
        $doctrineConfiguration->setProxyDir(ROOT_PATH . '/var/doctrine-proxies');
        $doctrineConfiguration->setProxyNamespace('DoctrineFirebirdDriver\Proxies');
        $doctrineConfiguration->setAutoGenerateProxyClasses(true);
        return $doctrineConfiguration;
    }

    /**
     * @return array
     */
    protected static function getSetUpDoctrineConfigurationArray(array $overrideConfigs = [])
    {
        // Originales Array
        /*
        return [
            'host' => 'localhost',
            'dbname' => static::DEFAULT_DATABASE_FILE_PATH,
            'user' => static::DEFAULT_DATABASE_USERNAME,
            'password' => static::DEFAULT_DATABASE_PASSWORD,
            'charset' => 'UTF-8',
        ]; */
        
         // Test-Array - NICHT IN PRODUKTION VERWENDEN!
         return [
            'host' => '10.1.12.200',
            'dbname' => "/srv/firebird/doctrine.fdb",
            'user' => "sysdba",
            'password' => "masterkey",
            'charset' => 'UTF-8',
        ];
    }
}
