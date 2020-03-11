<?php

// -------------------------------------------------------------------------
// Database Configurations
// -------------------------------------------------------------------------
// $var = include 'mysql.conf.php';
$cfg = include 'sqlite.conf.php';

$cfg['test'] = true;
$cfg['debug'] = true;

$cfg['timezone'] = 'Europe/Berlin';

// Set the debug variable to true to force the recompilation of all
// the templates each time during development
$cfg['installed_apps'] = array(
    'Pluf',
    'NoteBook'
);

// Default mimetype of the document your application is sending.
// It can be overwritten for a given response if needed.
$cfg['mimetype'] = 'text/html';

$cfg['app_base'] = '/testapp';
$cfg['url_format'] = 'simple';

// Temporary folder where the script is writing the compiled templates,
// cached data and other temporary resources.
// It must be writeable by your webserver instance.
// It is mandatory if you are using the template system.
$cfg['tmp_folder'] = '/tmp';

// -------------------------------------------------------------------------
// Template manager and compiler
// -------------------------------------------------------------------------

// The folder in which the templates of the application are located.
$cfg['templates_folder'] = array(
    dirname(__FILE__) . '/../templates'
);

$cfg['template_tags'] = array(
    'mytag' => 'Pluf_Template_Tag_Mytag'
);

$cfg['template_modifiers'] = array();
// -------------------------------------------------------------------------
// Logger
// -------------------------------------------------------------------------

//
// All possible levels
//
// all
// debug
// info
// notice
// warning
// error
// critical
// alert
// emergency
// off
//
$cfg['log_level'] = 'error';

$cfg['log_delayed'] = false;

//
// Formatter convert runtime date into a simple writable message.
//
$cfg['log_formater'] = '\Pluf\LoggerFormatter\Plain';

//
// Logger appender get a message and append a log to outputs such as consoel
// file remote server and etc.
//
$cfg['log_appender'] = '\Pluf\LoggerAppender\Console';

//
// Remote
//
// $cfg['log_appender_remote_server'] = 'localhost';
// $cfg['log_appender_remote_path'] = '/';
// $cfg['log_appender_remote_port'] = 8000;
// $cfg['log_appender_remote_headers'] = [];

// -------------------------------------------------------------------------
// Tenants
// -------------------------------------------------------------------------

// multitenant
return $cfg;
