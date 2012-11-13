Migrations
============

Установка
-------------
в процессе написания документации

Использование 
-------------

Список добавляемых консольных комманд
db_migrations_version - возвращает номер текущей версии
db_migrations_migrate [<version>] - выполнить или откатить миграцию, можно указать необязательный параметр номера версии
db_migrations_generate - Сгенерировать каркас класса миграции

Все миграции по умолчанию будут хранится в каталоге 
/project/migrations/*
поэтому нужно создать папку migrations или запускать команду генерации каркаса миграций с правами на запись в корневую директорию

В общем случае классы миграций должны иметь название вида 
Versionггггммддччммссс.php и реализовывать интерфейс Migrations\Library\MigrationInterface

Пример класса миграции
``` php
<?php

namespace Migrations\Migrations;

use Migrations\Library\AbstractMigration;
use Zend\Db\Metadata\MetadataInterface;

class Version20121112230913 extends AbstractMigration {
    
    public function up(MetadataInterface $schema){
        //$this->addSql(/*Sql instruction*/);
    }
    
    public function down(MetadataInterface $schema){
        //$this->addSql(/*Sql instruction*/);
    }
}
```

выполнить миграцию можно двумя способами
запустив команду db_migrations_migrate без параметров
или с указанием версии 
db_migrations_migrate 20121112230913

Version20121112230913 - здесь 20121112230913 будет версией миграции

Смотрите блог http://vadim-knyzev.blogspot.com/