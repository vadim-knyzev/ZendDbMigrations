<?php

namespace ZendDbMigrations\Library;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Adapter\Driver\Pdo\Pdo;
use Zend\Db\Metadata\Metadata;
use ZendDbMigrations\Library\MigrationInterface;
use ZendDbMigrations\Library\OutputWriter;

/**
 * Основная логика работы с миграциями
 */
class Migration {
    
    protected $migrationTable = 'migration_version';
    protected $migrationClassFolder;
    protected $namespaceMigrationsClasses;
    protected $adapter;
    protected $connection;
    protected $metadata;
    protected $outputWriter;

    /**
     * Конструктор
     * @param \Zend\Db\Adapter\Adapter $adapter
     * @param string $migrationClassFolder каталог с классами миграций
     */
    public function __construct(Adapter $adapter, $migrationClassFolder, $namespaceMigrationsClasses, OutputWriter $writer = null) {
        $this->adapter = $adapter;
        $this->metadata = new Metadata($this->adapter);
        $this->connection = $this->adapter->getDriver()->getConnection();
        $this->migrationClassFolder = $migrationClassFolder;
        $this->namespaceMigrationsClasses = $namespaceMigrationsClasses;
        $this->outputWriter = is_null($writer) ? new OutputWriter() : $writer;
        
        if(is_null($migrationClassFolder))
            throw new \Exception('Unknown directory!');
        
        if(is_null($namespaceMigrationsClasses))
            throw new \Exception('Unknown namespaces!');
        
        if(!file_exists($this->migrationClassFolder))
            if(!mkdir ($this->migrationClassFolder, 0775))
                    throw new \Exception(sprintf('Not permitted to created directory %s', 
                            $this->migrationClassFolder));
            
            
    }
    
    /**
     * Создать таблицу миграций
     */
    protected function createMigrationTable(){
        $sql = sprintf('CREATE TABLE IF NOT EXISTS `%s` (`id`  SERIAL NOT NULL, 
            `version` bigint NOT NULL, 
            PRIMARY KEY (`id`));', $this->migrationTable);
        $this->connection->execute($sql);
    }
    
    /**
     * Получить текущий номер версии
     * @return integer Номер текущей миграции
     */
    public function getCurrentVersion(){
        $this->createMigrationTable();
        $result = $this->connection->execute(sprintf('SELECT version FROM %s ORDER BY version DESC LIMIT 1', 
                $this->migrationTable));
        
        if($result->count() == 0)
            return 0;
        
        $version = $result->current();
        
        return $version['version'];
    }
    
    /**
     * Добавить сведения о выполненной миграции
     * @param integer $version
     */
    protected function markMigrated($version)
    {
        $this->createMigrationTable();
        $this->connection->execute(sprintf("INSERT INTO %s (version) VALUES (%s)", 
                $this->migrationTable,
                $version
                ));
    }

    /**
     * Удалить сведения о выполенной миграции
     * @param string $version
     */
    protected function markNotMigrated($version)
    {
        $this->createMigrationTable();
        $this->connection->execute(sprintf("DELETE FROM %s WHERE version=%s", 
                $this->migrationTable,
                $version
                ));
    }
    
    /**
     * Проверка выполнена ли миграция
     * @param integer version
     * @return boolean
     */
    public function checkExecuteMigration($version){
        $this->createMigrationTable();
        $result = $this->connection->execute(sprintf('SELECT version FROM %s WHERE version=%s', 
                $this->migrationTable, $version));
        
        return $result->count() > 0;
    }

    /**
     * Мигрировать
     * @param integer $version Номер версии к которой нужно мигрировать, 
     * если не указано то будут выполнены все новые миграции
     * @throws MigrationException
     */
    public function migrate($version = null){
        $migrations = $this->getMigrationClasses();
        
        if(!is_null($version) && !$this->hasMigrationVersion($migrations, $version)){
            throw new MigrationException(sprintf('Migration version %s is not found!', $version));
        }
        
        
        $currentMigrationVersion = $this->getCurrentVersion();
        
        if(!is_null($version) && $version == $currentMigrationVersion)
            throw new MigrationException(sprintf('Migration version %s is current version!', $version));
        
        
        $this->connection->beginTransaction();
        try {
            
            //номер миграции не указан либо указанный номер больше последней выполненной миграции -> миграция добавления
            if(is_null($version) || (!is_null($version) && $version > $currentMigrationVersion))
            {
                foreach($migrations as $migration)
                {
                    $migrationObject = new $migration['class']($this->metadata);
                    
                    if($migration['version'] > $currentMigrationVersion)
                    {
                        if(is_null($version) || (!is_null($version) && $version >= $migration['version']))
                        {
                            $this->outputWriter->write(sprintf("Execute migration class %s up", $migration['class']));
                            
                            foreach($migrationObject->getUpSql() as $sql){
                                $this->connection->execute($sql);
                                $this->outputWriter->write("Execute sql code  \n\n" . $sql . "\n");
                            }
                        
                            $this->markMigrated($migration['version']);
                        }
                    }
                }
            }
            //номер миграции указан и версия ниже текущей -> откат миграции
            else if(!is_null($version) && $version < $currentMigrationVersion)
            {
                $migrationsByDesc = $this->sortMigrationByVersionDesc($migrations);
                foreach($migrationsByDesc as $migration)
                {
                    $migrationObject = new $migration['class']($this->metadata);

                    if($migration['version'] > $version && $migration['version'] <= $currentMigrationVersion)
                    {
                        $this->outputWriter->write(sprintf("Execute migration class %s down", $migration['class']));
                        
                        foreach($migrationObject->getDownSql() as $sql){
                            $this->connection->execute($sql);
                                $this->outputWriter->write("Execute sql code  \n\n" . $sql . "\n");
                        }
                    
                        $this->markNotMigrated($migration['version']);
                    }

                }
            }
            
            $this->connection->commit();
        }
        catch(\Exception $e){
            $this->connection->rollback();
            throw new MigrationException($e->getMessage() . '; Line error %' . $e->getLine());
        }
        
    }
    
    /**
     * Отсортировать миграции по версии в обратном порядке
     * @param \ArrayIterator $migrations
     * @return \ArrayIterator
     */
    public function sortMigrationByVersionDesc(\ArrayIterator $migrations){
        $sortedMigrations = clone $migrations;
        
        $sortedMigrations->uasort(function($a, $b){
            if ($a['version'] == $b['version']) {
                return 0;
            }
            
            return ($a['version'] > $b['version']) ? -1 : 1;
        });
        
        return $sortedMigrations;
    }
    
    /**
     * Проверить существование класса для номера миграции
     * @param \ArrayIterator $migrations
     * @param integer $version
     * @return boolean
     */
    public function hasMigrationVersion(\ArrayIterator $migrations, $version){
        $classes = new \ArrayIterator(array());
        
        $versions = array();
        foreach($migrations as $migration){
            $versions[] = $migration['version'];
        }
        
        return in_array($version, $versions) || $version == 0;
    }
    
    /**
     * Получить номер максимальной версии миграции
     * @param \ArrayIterator $migrations
     * @return integer
     */
    public function getMaxMigrationNumber(\ArrayIterator $migrations){
        $classes = new \ArrayIterator(array());
        
        $versions = array();
        foreach($migrations as $migration){
            $versions[] = $migration['version'];
        }
        
        sort($versions, SORT_NUMERIC);
        $versions = array_reverse($versions);
        
        return count($versions) > 0 ? $versions[0] : 0;
    }

    /**
     * Найти список классов миграций
     * @return \ArrayIterator [version,class]
     */
    public function getMigrationClasses(){
        
        $classes = new \ArrayIterator();
        
        $iterator = new \GlobIterator(sprintf('%s/Version*.php', $this->migrationClassFolder), \FilesystemIterator::KEY_AS_FILENAME);
        foreach ($iterator as $item) {
            if(preg_match('/(Version(\d+))\.php/', $item->getFilename(), $matches)){
                
                $className = $this->namespaceMigrationsClasses . '\\' . $matches[1];
                
                if(!class_exists($className))
                    require_once $this->migrationClassFolder . '/' . $item->getFilename();
                
                if(class_exists($className))
                {
                    $reflection = new \ReflectionClass($className);
                    if($reflection->implementsInterface('ZendDbMigrations\Library\MigrationInterface'))
                    {
                        $classes->append(array(
                            'version' => $matches[2],
                            'class' => $className
                        ));
                    }
                }
            }
        }
        
        $classes->uasort(function($a, $b){
            if ($a['version'] == $b['version']) {
                return 0;
            }
            
            return ($a['version'] < $b['version']) ? -1 : 1;
        });
        
        return $classes;
    }
}

?>
