<?php
require_once dirname(__FILE__) . '/../../../../tests/tests_halo/SeleniumTestCase_Base.php';

class TestQIInitFromGetParams2 extends SeleniumTestCase_Base
{

  public function testMyTestCase()
  {
    $this->open("/mediawiki/index.php?title=Special:QueryInterface&query=%5B%5BCategory%3APerson%5D%5D%7C%3FLastname");
    for ($second = 0; ; $second++) {
        if ($second >= 60) $this->fail("timeout");
        try {
            if ($this->isElementPresent("link=Person")) break;
        } catch (Exception $e) {}
        sleep(1);
    }

    $this->assertTrue($this->isElementPresent("link=Lastname"));
    $this->assertTrue($this->isTextPresent("= all values"));
  }
}
?>