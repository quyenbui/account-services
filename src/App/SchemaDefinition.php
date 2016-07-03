<?php

namespace App;

use Doctrine\DBAL\Schema\Schema;

class SchemaDefinition
{
    public function schema()
    {
        $schema = new Schema();
        $accounts = $schema->createTable('accounts');
        $accounts->addColumn('uid', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $accounts->addColumn('status', 'boolean', ['unsigned' => true, 'default' => 1]);
        $accounts->addColumn('first_name', 'string', ['length' => 35, 'notnull' => false]);
        $accounts->addColumn('last_name', 'string', ['length' => 35, 'notnull' => false]);
        $accounts->addColumn('avatar', 'string', ['length' => 255, 'notnull' => false]);
        $accounts->addColumn('email', 'string', ['length' => 255, 'notnull' => true]);
        $accounts->addColumn('password', 'string', ['length' => 255, 'notnull' => true]);
        $accounts->addColumn('salt', 'string', ['length' => 255, 'notnull' => true]);
        $accounts->addColumn('created', 'integer', ['unsigned' => true, 'notnull' => true]);
        $accounts->addColumn('updated', 'integer', ['unsigned' => true, 'notnull' => true]);
        $accounts->setPrimaryKey(['uid']);
        $accounts->addUniqueIndex(['email'], 'userEmail');
        $accounts->addIndex(['first_name', 'last_name']);
        $accounts->addIndex(['uid']);
        $accounts->addIndex(['status']);
        $accounts->addIndex(['email']);
        $accounts->addIndex(['password']);

        return $schema;
    }
}
