<?php

class PapayaModuleBingPageSearchTest extends PapayaTestCase {

  public function testNoQueryExpectingNoAppendedNodes() {
    $search = new PapayaModuleBingApiSearch(
      '', '', ''
    );
    $search->papaya($this->mockPapaya()->application());
    $document = new PapayaXmlDocument();
    $result = $document->appendElement('test');
    $result->append($search);
    $this->assertXmlStringEqualsXmlString(
      '<test/>',
      $result->saveXml()
    );
  }

}

