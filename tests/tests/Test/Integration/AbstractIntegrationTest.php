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

    public function CleanUp() {
        
        if($this->_entityManager != null) {
            
            $sqlCleanupCmd = "DROP TABLE TABLE_8858821D435F; COMMIT; 
DROP TABLE TABLE_65B8AE43661D; COMMIT; 
DROP TABLE TABLE_C987B912D868; COMMIT; 
DROP TABLE TABLE_579C7654E485; COMMIT; 
DROP TABLE TABLE_A00126DCC92F; COMMIT; 
DROP TABLE TABLE_F2ADD69E2F0E; COMMIT; 
DROP TABLE TABLE_56E7118E5840; COMMIT; 
DROP TABLE TABLE_4097CA8B6DF6; COMMIT; 
DROP TABLE TABLE_1B0B51648DF7; COMMIT; 
DROP TABLE TABLE_F6BE77F795F4; COMMIT; 
DROP TABLE TABLE_907A9FDF2D55; COMMIT; 
DROP TABLE TABLE_9EDE90DF6C7A; COMMIT; 
DROP TABLE TABLE_CE5DD0DD4EEE; COMMIT; 
DROP TABLE TABLE_42FB6A8058BF; COMMIT; 
DROP TABLE TABLE_3AFCCAC09282; COMMIT; 
DROP TABLE TABLE_5A95131F163E; COMMIT; 
DROP TABLE TABLE_5CF10FDEFFC4; COMMIT; 
DROP TABLE TABLE_D5C08625650E; COMMIT; 
DROP TABLE TABLE_89B8B5BBD6DA; COMMIT; 
DROP TABLE TABLE_B90AB5FDF5FF; COMMIT; 
DROP TABLE TABLE_EEEF033261CB; COMMIT; 
DROP TABLE TABLE_77D1E59268CE; COMMIT; 
DROP TABLE TABLE_C8C505BE207E; COMMIT; 
DROP TABLE TABLE_E356AECDB971; COMMIT; 
DROP TABLE TABLE_E7E748AE4D91; COMMIT; 
DROP TABLE TABLE_FF02D4206625; COMMIT; 
DROP TABLE TABLE_A658E7D9CEC6; COMMIT; 
DROP TABLE TABLE_A8C51F3CC54B; COMMIT; 
DROP TABLE TABLE_69F695769988; COMMIT; 
DROP TABLE TABLE_C73EBDA74987; COMMIT; 
DROP TABLE TABLE_18858A14284D; COMMIT; 
DROP TABLE TABLE_E9876C8EEDBD; COMMIT; 
DROP TABLE TABLE_BF19B31B26E3; COMMIT; 
DROP TABLE TABLE_9B69CFC943A4; COMMIT; 
DROP TABLE TABLE_3109424AF852; COMMIT; 
DROP TABLE TABLE_66877CF1719F; COMMIT; 
DROP TABLE TABLE_7FB1A7BC8856; COMMIT; 
DROP TABLE TABLE_F409F8FD7E34; COMMIT; 
DROP TABLE TABLE_45808E8FF881; COMMIT; 
DROP TABLE TABLE_11325AE7AEF5; COMMIT; 
DROP SEQUENCE TABLE_18858A14284D_D2IS; COMMIT; 
DROP SEQUENCE TABLE_A8C51F3CC54B_D2IS; COMMIT; 
DROP SEQUENCE TABLE_C73EBDA74987_D2IS; COMMIT;
";
            
            $connection = $this->_entityManager->getConnection();
            
            // execute all cleanup statements one-by-one
            // to avoid that a thrown exception e.g because of a missing table
            // results in an incomplete clean up
            $sqlCleanUpCmdArr = explode("\n", $sqlCleanupCmd);
            for($j = 0; $j < count($sqlCleanUpCmdArr); $j++) {
                try {
                    $currentSql = $sqlCleanUpCmdArr[$j];
                    $connection->exec($currentSql);
                } catch (Exception $ex) {
                    
                } catch(\Doctrine\DBAL\DBALException $ex) {
                    // IMPORTANT!
                    // this exception has to be handled separately 
                    // as catch(Exception $ex) does not catch DBALExceptions
                    // --
                    // [!] Without this catch-block the loop stops after the first error
                }
            }
        }
    }
    
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
            $this->CleanUp();
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
