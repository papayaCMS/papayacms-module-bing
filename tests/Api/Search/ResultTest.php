<?php

class PapayaModuleBingPageSearchResultTest extends PapayaTestCase {

  private static $_VALID_RESULT = array(
    '_type' => 'SearchResponse',
    'queryContext' => array(
      'originalQuery' => 'C50'
    ),
    'webPages' => array(
      'totalEstimatedMatches' => 1,
      'value' => array(
        array(
          'id' => 'https://api.cognitive.microsoft.com/api/v7/#WebPages.0',
          'name' => 'Metastasierter Brustkrebs - gesundheitsinformation.de',
          'url' => 'https://www.gesundheitsinformation.de/metastasierter-brustkrebs.2361.de.html',
          'isFamilyFriendly' => TRUE,
          'displayUrl' => 'https://www.gesundheitsinformation.de/metastasierter-brustkrebs...',
          'snippet' => 'Manchmal wird Brustkrebs festgestellt, wenn er schon Metastasen gebildet hat. Wir informieren über Behandlungen und das Leben mit fortgeschrittenem ...',
          'dateLastCrawled' => '2018-03-08T06:43:00.0000000Z',
          'fixedPosition' => FALSE
        )
      )
    )
  );

  public function testGetEstimatedMatchesExpecting1() {
    $result = new PapayaModuleBingApiSearchResult(self::$_VALID_RESULT);
    $this->assertSame(1, $result->getEstimatedMatches());
  }

  public function testGetQueryExpectingC50() {
    $result = new PapayaModuleBingApiSearchResult(self::$_VALID_RESULT);
    $this->assertSame('C50', $result->getQuery());
  }

}

