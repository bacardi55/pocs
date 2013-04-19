<?php

/**
 * @author Raphael Khaiat <admin@bacardi55.org>
 *
 * POCS schema.
 */

$schema = new \Doctrine\DBAL\Schema\Schema();

$admins = $schema->createTable('admin');
$admins->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
$admins->addColumn('login', 'string', array('length' => 55));
$admins->addColumn('password', 'string', array('length' => 55));
$admins->addColumn('email', 'string', array('length' => 55));
$admins->addUniqueIndex(array('email'));
$admins->setPrimaryKey(array('id'));

$users = $schema->createTable('users');
$users->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
$users->addColumn('name', 'string', array('length' => 55));
$users->addColumn('email', 'string', array('length' => 55));
$users->addUniqueIndex(array("email"));
$users->setPrimaryKey(array('id'));

$frontends = $schema->createTable('frontends');
$frontends->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
$frontends->addColumn('base_url', 'string', array('length' => 100));
$frontends->addColumn('unique_name', 'string', array('length' => 100));
$frontends->addColumn('key', 'string', array('length' => 254));
$frontends->addUniqueIndex(array("base_url"));
$frontends->setPrimaryKey(array('id'));

$urls = $schema->createTable('urls');
$urls->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
$urls->addColumn('frondend_id', 'integer');
$urls->addColumn('url', 'string', array('length' => 254));
$urls->addUniqueIndex(array("url"));
$urls->setPrimaryKey(array('id'));

$comments = $schema->createTable('comments');
$comments->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
$comments->addColumn('user_id', 'integer');
$comments->addColumn('url_id', 'integer');
$comments->addColumn('comment', 'text');
$comments->setPrimaryKey(array('id'));

return $schema;
