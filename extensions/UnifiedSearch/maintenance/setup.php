<?php
/**
 * Setup database for Unified search extension.
 * 
 * @author: Kai K�hn
 * 
 * Created on: 27.01.2009
 */
if (array_key_exists('SERVER_NAME', $_SERVER) && $_SERVER['SERVER_NAME'] != NULL) {
    echo "Invalid access! A maintenance script MUST NOT accessed from remote.";
    return;
}

$mediaWikiLocation = dirname(__FILE__) . '/../../..';
require_once "$mediaWikiLocation/maintenance/commandLine.inc";

print "\nSetup database for Unified search.\n\n";

wfUSInitialize();

?>