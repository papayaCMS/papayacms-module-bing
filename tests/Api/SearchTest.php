<?php

namespace Papaya\Module\Bing\Api;

class SearchTest extends \PapayaTestCase {

  public function testNoQueryExpectingNoAppendedNodes() {
    $search = new Search(
      '', '', '', ''
    );
    $search->papaya($this->mockPapaya()->application());
    $document = new \PapayaXmlDocument();
    $result = $document->appendElement('test');
    $result->append($search);
    $this->assertXmlStringEqualsXmlString(
      '<test/>',
      $result->saveXml()
    );
  }

}

