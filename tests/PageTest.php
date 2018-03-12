<?php

class PapayaModuleBingPageTest extends PapayaTestCase {

  public function testSearchApiGetAfterSet() {
    $page = new PapayaModuleBingPage(
      $this->createMock('PapayaObject')
    );
    $searchApi = $this->createMock('PapayaModuleBingApiSearch');
    $page->searchApi($searchApi);
    $this->assertSame($searchApi, $page->searchApi());
  }

}

