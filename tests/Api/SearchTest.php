<?php

namespace Papaya\Module\Bing\Api;

class SearchTest extends \PapayaTestCase {

  public function testNoQueryExpectingError() {
    $search = new Search(
      '/search', 'abc', '123'
    );
    $document = new \PapayaXmlDocument();
    $result = $document->appendElement('test');
    $result->append($search->fetch(''));
    $this->assertXmlStringEqualsXmlString(
      '<test><error identifier="EmptyQuery" severity="warning"/></test>',
      $result->saveXml()
    );
  }

}

