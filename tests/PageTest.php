<?php

namespace Papaya\Module\Bing;

class PageTest extends \PapayaTestCase {

  public function testSearchApiGetAfterSet() {
    $page = new Page(
      $this->createMock(\PapayaObject::class)
    );
    $searchApi = $this->createMock(Api\Search::class);
    $page->searchApi($searchApi);
    $this->assertSame($searchApi, $page->searchApi());
  }

}

