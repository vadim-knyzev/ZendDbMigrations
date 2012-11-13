ZendDbMigrations
============

Установка
-------------


Использование 
-------------

Список добавляемых консольных комманд\n
db_migrations_version - возвращает номер текущей версии\n
db_migrations_migrate [<version>] - выполнить или откатить миграцию, можно указать необязательный параметр номера версии\n
db_migrations_generate - Сгенерировать каркас класса миграции\n

Все миграции по умолчанию будут хранится в каталоге \n
/project/migrations/*\n
поэтому нужно создать папку migrations или запускать команду генерации каркаса миграций с правами на запись в корневую директорию\n

В общем случае классы миграций должны иметь название вида \n
Versionггггммддччммссс.php и реализовывать интерфейс ZendDbMigrations\Library\MigrationInterface\n

Пример класса миграции\n
``` php
<?php

namespace ZendDbMigrations\Migrations;

use ZendDbMigrations\Library\AbstractMigration;
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

выполнить миграцию можно двумя способами\n
запустив команду db_migrations_migrate без параметров\n
или с указанием версии \n
db_migrations_migrate 20121112230913\n

Version20121112230913 - здесь 20121112230913 будет версией миграции\n

http://vadim-knyzev.blogspot.com/