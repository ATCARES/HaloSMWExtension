<?php

require_once( dirname(__FILE__) . '/../../maintenance/commandLine.inc' );
ini_set( 'include_path', get_include_path() . PATH_SEPARATOR . /*$_SERVER['PHP_PEAR_INSTALL_DIR']*/ 'C:\php\pear' );
require_once( 'PHPUnit/Autoload.php' );
PHPUnit_TextUI_Command::main(true);

