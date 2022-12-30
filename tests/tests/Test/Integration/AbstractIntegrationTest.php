<?php
namespace Kafoso\DoctrineFirebirdDriver\Test\Integration;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;
use Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase;
use Kafoso\DoctrineFirebirdDriver\Platforms\FirebirdInterbasePlatform;

abstract class AbstractIntegrationTest extends \PHPUnit\Framework\TestCase
{
    const DEFAULT_DATABASE_FILE_PATH = '/opt/firebird/db/music_library.fdb';
    const DEFAULT_DATABASE_USERNAME = 'SYSDBA';
    const DEFAULT_DATABASE_PASSWORD = 'masterkey';
    const DEFAULT_DATABASE_HOST = 'localhost';
    const DEFAULT_ISQLFB_PATH = "/opt/firebird/bin/isql";

    protected $_entityManager;
    protected $_platform;

    public function setUp(): void
    {
        $doctrineConfiguration = static::getSetUpDoctrineConfiguration();
        $configurationArray = static::getSetUpDoctrineConfigurationArray();
        static::installFirebirdDatabase($configurationArray);
        $this->_entityManager = static::createEntityManager($doctrineConfiguration, $configurationArray);
        $this->_platform = new FirebirdInterbasePlatform;
    }

    public function tearDown(): void
    { 
        if ($this->_entityManager) {
            $this->_entityManager->getConnection()->close();
        }
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
        /*
        if (file_exists($configurationArray['dbname'])) {
            unlink($configurationArray['dbname']); // Don't do this outside tests
        }
        */
        
        $createSqlScript = "SET SQL DIALECT 3;
                            SET NAMES UTF8;
                            CREATE DATABASE '" . ($configurationArray['host'] ? $configurationArray['host'] . ':' . $configurationArray['dbname'] : $configurationArray['dbname']) . "' USER '" . $configurationArray['user'] . "' PASSWORD '" . $configurationArray['password'] . "' PAGE_SIZE 16384 DEFAULT CHARACTER SET UTF8;";
        
        $fHandle = tmpfile();
        $path = stream_get_meta_data($fHandle)["uri"];
        fwrite($fHandle, $createSqlScript);
        

        $cmd = sprintf(self::DEFAULT_ISQLFB_PATH . " -input %s 2>&1", escapeshellarg($path));
        exec($cmd);
        //unlink($path);
        fclose($fHandle); // close handle here, because cleanup is assumed to delete temporary file
        
        $cmd = sprintf(
            self::DEFAULT_ISQLFB_PATH . " %s -input %s -password %s -user %s",
            $configurationArray['host'] ? $configurationArray['host'] . ':' . $configurationArray['dbname'] : $configurationArray['dbname'],
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
        return [
            'host' => static::DEFAULT_DATABASE_HOST,
            'dbname' => static::DEFAULT_DATABASE_FILE_PATH,
            'user' => static::DEFAULT_DATABASE_USERNAME,
            'password' => static::DEFAULT_DATABASE_PASSWORD,
            'charset' => 'UTF-8',
        ];
    }
}
