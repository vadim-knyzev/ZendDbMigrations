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
use Zend\Mvc\MvcEvent;
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
     * @var \ZendDbMigrations\Library\Migration
     */
    protected $migration;
    /**
     * @var \ZendDbMigrations\Model\MigrationVersionTable
     */
    protected $migrationVersionTable;
    /**
     * @var OutputWriter
     */
    protected $output;

    public function onDispatch(MvcEvent $e)
    {
        if (!$this->getRequest() instanceof ConsoleRequest) {
            throw new \RuntimeException('You can only use this action from a console!');
        }

        /** @var $adapter \Zend\Db\Adapter\Adapter */
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $config = $this->getServiceLocator()->get('Configuration');

        $console = $this->getServiceLocator()->get('console');

        if ($config['migrations']['show_log']) {
            $this->output = new OutputWriter(function ($message) use ($console) {
                $console->write($message . "\n");
            });
        }

        $this->migrationVersionTable = $this->getServiceLocator()->get('ZendDbMigrations\Model\MigrationVersionTable');
        $this->migration = new Migration($adapter, $config['migrations'], $this->migrationVersionTable, $this->output);

        return parent::onDispatch($e);
    }

    /**
     * Получить текущую версию миграции
     * @return integer
     */
    public function versionAction()
    {
        return sprintf("Current version %s\n", $this->migrationVersionTable->getCurrentVersion());
    }

    public function listAction()
    {
        $migrations = $this->migration->getMigrationClasses($this->getRequest()->getParam('all'));
        $list = array();
        foreach ($migrations as $m) {
            $list[] = sprintf("%s %s - %s", $m['applied'] ? '-' : '+', $m['version'], $m['description']);
        }
        return (empty($list) ? 'No migrations to execute.' : implode("\n", $list)) . "\n";
    }

    /**
     * Мигрировать
     */
    public function migrateAction()
    {
        $version = $this->getRequest()->getParam('version');

        $migrations = $this->migration->getMigrationClasses();
        $currentMigrationVersion = $this->migrationVersionTable->getCurrentVersion();
        if (is_null($version) && $currentMigrationVersion >= $this->migration->getMaxMigrationNumber($migrations))
            return "No migrations to execute.\n";

        try {
            $this->migration->migrate($version);
            return "Migrations executed!\n";
        } catch (MigrationException $e) {
            return "ZendDbMigrations\\Library\\MigrationException\n" . $e->getMessage() . "\n";
        }
    }

    /**
     * Сгенерировать каркасный класс для новой миграции
     */
    public function generateMigrationClassAction()
    {
        $config = $this->getServiceLocator()->get('Configuration');

        $generator = new GeneratorMigrationClass($config['migrations']['dir'], $config['migrations']['namespace']);
        $className = $generator->generate();

        return sprintf("Generated class %s\n", $className);
    }
}