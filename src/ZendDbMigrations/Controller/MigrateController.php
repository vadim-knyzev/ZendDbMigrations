<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDbMigrations\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Console\Request as ConsoleRequest;
use ZendDbMigrations\Library\Migration;
use ZendDbMigrations\Library\MigrationException;
use ZendDbMigrations\Library\GeneratorMigrationClass;
use ZendDbMigrations\Library\OutputWriter;

/**
 * Контроллер обеспечивает вызов комманд миграций
 */
class MigrateController extends AbstractActionController
{
    /**
     * Создать объект класса миграций
     * @return \Migrations\Library\Migration
     */
    protected function getMigration(){
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $config = $this->getServiceLocator()->get('Configuration');
        
        $console = $this->getServiceLocator()->get('console');
        
        $output = null;
        
        if($config['migrations']['show_log'])
        {
            $output = new OutputWriter(function($message) use($console) {
                        $console->write($message . "\n");
                });
        }
        
        return new Migration($adapter, $config['migrations']['dir'], $config['migrations']['namespace'], $output);
    }
    
    /**
     * Получить текущую версию миграции
     * @return integer
     */
    public function versionAction(){
        $migration = $this->getMigration();
        
        return sprintf("Current version %s\n", $migration->getCurrentVersion());
    }
    
    /**
     * Мигрировать
     */
    public function migrateAction(){
        $migration = $this->getMigration();
        
        $version = $this->getRequest()->getParam('version');
        
        if(is_null($version) && $migration->getCurrentVersion() >= $migration->getMaxMigrationNumber($migration->getMigrationClasses()))
            return "No migrations to execute.\n";
        
        try{
            $migration->migrate($version);
            return "Migrations executed!\n";
        }
        catch (MigrationException $e) {
            return "ZendDbMigrations\Library\MigrationException\n" . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Сгенерировать каркасный класс для новой миграции
     */
    public function generateMigrationClassAction(){
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $config = $this->getServiceLocator()->get('Configuration');
        
        $generator = new GeneratorMigrationClass($config['migrations']['dir'], $config['migrations']['namespace']);
        $className = $generator->generate();
        
        return sprintf("Generated class %s\n", $className);
    }
}