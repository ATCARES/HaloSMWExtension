<?php
/**
 * @file
 * @ingroup LinkedData_Tests
 */

require_once 'PHPUnit/Framework.php';
 
require_once 'testcases/TestLODSourceDefinition.php';
require_once 'testcases/TestTripleStoreAccess.php';
require_once 'testcases/TestMapping.php';
require_once 'testcases/TestSparqlDataspaceRewriter.php';
require_once 'testcases/TestOntologyBrowserSparql.php';
require_once 'testcases/TestMappingLanguageAPI.php';

class LODTests
{ 
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PHPUnit');
        
        $suite->addTestSuite("TestTripleStoreAccess");
        $suite->addTestSuite("TestLODSourceDefinition");
        $suite->addTestSuite("TestMapping");
        $suite->addTestSuite("TestSparqlDataspaceRewriter");
        $suite->addTestSuite("TestOntologyBrowserSparql");
        $suite->addTestSuite("TestMappingLanguageAPI");

        return $suite;
    }
}