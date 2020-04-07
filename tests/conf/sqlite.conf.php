<?php
$cfg = array();

$cfg['db_engine'] = '\Pluf\Db\SQLiteEngine';
$cfg['db_database'] = '/tmp/pluf.test.sqlite.db';

$cfg['db_schema'] = '\Pluf\Db\SQLiteSchema';
$cfg['db_schema_table_prefix'] = 'pluf_unit_tests_' . rand() . '_';

return $cfg;

