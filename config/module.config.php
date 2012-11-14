<?php

return array(
    'migrations' => array(
        'dir' => dirname(__FILE__) . '/../../../../migrations',
        'namespace' => 'ZendDbMigrations\Migrations',
        'show_log' => true
    ),
    'console' => array(
        'router' => array(
            'routes' => array(
                'db_migrations_version' => array(
                    'type'    => 'simple',
                    'options' => array(
                        'route'    => 'db_migrations_version [--env=]',
                        'defaults' => array(
                            'controller' => 'ZendDbMigrations\Controller\Migrate',
                            'action'     => 'version'
                        )
                    )
                ),
                'db_migrations_migrate' => array(
                    'type'    => 'simple',
                    'options' => array(
                        'route'    => 'db_migrations_migrate [<version>] [--env=]',
                        'defaults' => array(
                            'controller' => 'ZendDbMigrations\Controller\Migrate',
                            'action'     => 'migrate'
                        )
                    )
                ),
                'db_migrations_generate' => array(
                    'type'    => 'simple',
                    'options' => array(
                        'route'    => 'db_migrations_generate [--env=]',
                        'defaults' => array(
                            'controller' => 'ZendDbMigrations\Controller\Migrate',
                            'action'     => 'generateMigrationClass'
                        )
                    )
                )
            )
        )
    ),
    'controllers' => array(
        'invokables' => array(
            'ZendDbMigrations\Controller\Migrate' => 'ZendDbMigrations\Controller\MigrateController'
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
);
