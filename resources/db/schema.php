<?php

/**
 * @author Raphael Khaiat <admin@bacardi55.org>
 *
 * POCS schema.
 */

$schema = new \Doctrine\DBAL\Schema\Schema();

$admins = $schema->createTable('admins');
$admins->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
$admins->addColumn('email', 'string', array('length' => 55));
$admins->addColumn('password', 'string', array('length' => 255));
$admins->addUniqueIndex(array('email'));
$admins->setPrimaryKey(array('id'));

$frontends = $schema->createTable('frontends');
$frontends->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
$frontends->addColumn('base_url', 'string', array('length' => 100));
$frontends->addColumn('name', 'string', array('length' => 100));
$frontends->addColumn('apikey', 'string', array('length' => 254));
$frontends->addUniqueIndex(array("base_url"));
$frontends->setPrimaryKey(array('id'));

$urls = $schema->createTable('urls');
$urls->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
$urls->addColumn('frontend_id', 'integer');
$urls->addColumn('url', 'string', array('length' => 254));
$urls->addUniqueIndex(array("frontend_id", "url"));
$urls->setPrimaryKey(array('id'));

$comments = $schema->createTable('comments');
$comments->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
$comments->addColumn('user_name', 'string', array('length' => 100));
$comments->addColumn('user_email', 'string', array('length' => 100));
$comments->addColumn('url_id', 'integer');
$comments->addColumn('date', 'datetime');
$comments->addColumn('comment', 'text');
$comments->setPrimaryKey(array('id'));

return $schema;
