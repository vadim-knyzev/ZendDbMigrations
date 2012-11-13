<?php

namespace ZendDbMigrations\Library;

/**
 * Генерирование каркаса миграции
 */
class GeneratorMigrationClass {
    
    protected $migrationsFolder;
    protected $migrationNamespace;

    /**
     * Конструктор
     * @param type $folderMigrationClasses каталог в который сохранить сгенерированную миграцию
     * @param string $namespace Нэймспэйс указанный в классе миграции
     */
    public function __construct($folderMigrationClasses, $migrationNamespace) {
        $this->migrationsFolder = $folderMigrationClasses;
        $this->migrationNamespace = $migrationNamespace;
        
        
        if(!file_exists($this->migrationsFolder))
            if(!mkdir ($this->migrationsFolder, 0775))
                    throw new \Exception(sprintf('Not permitted to created directory %s', 
                            $this->migrationsFolder));
    }

    /**
     * Генерировать класс миграции
     * @return string Путь к классу
     * @throws \Exception
     */
    public function generate(){
        
        $className = sprintf('Version%s', date('YmdHis', time()));
        $classPath = $this->migrationsFolder . '/' . $className .'.php';
        
        if(!is_writable($this->migrationsFolder))
            throw new \Exception(sprintf('%s path is not writable!', $classPath));
        
        if(file_exists($classPath))
            throw new \Exception(sprintf('Migration %s if exists!', $className));
        
        file_put_contents($classPath, $this->getTemplate($className, $this->migrationNamespace));
        
        return $classPath;
    }
    
    /**
     * Шаблон для класса миграции
     * @param type $className
     * @param type $namespace
     * @return string
     */
    protected function getTemplate($className, $namespace){
        return sprintf('<?php

namespace %s;

use ZendDbMigrations\Library\AbstractMigration;
use Zend\Db\Metadata\MetadataInterface;

class %s extends AbstractMigration {
    
    public function up(MetadataInterface $schema){
        //$this->addSql(/*Sql instruction*/);
    }
    
    public function down(MetadataInterface $schema){
        //$this->addSql(/*Sql instruction*/);
    }
}', $namespace, $className);
    }
}

?>
