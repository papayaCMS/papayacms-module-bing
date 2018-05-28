<?php

namespace Papaya\Module\Bing\Api\Search;

class DecoratedTextTest extends \PapayaTestCase {

  /**
   * @param $expectedXml
   * @param $text
   * @dataProvider provideExamples
   */
  public function testAppendDecoratedText($expectedXml, $text) {
    $document = new \PapayaXmlDocument();
    $snippet = $document->appendElement('snippet');
    $text = new DecoratedText($text);
    $snippet->append($text);
    $this->assertXmlStringEqualsXmlString(
      $expectedXml,
      $snippet->saveXml()
    );
  }

  public static function provideExamples() {
    return array(
      array(
        '<snippet>before <marker type="query_term">term</marker> after</snippet>',
        html_entity_decode('before &#xE000;term&#xE001; after')
      ),
      array(
        '<snippet>before <marker type="address"><marker type="phone_number">+00 123 456789</marker></marker> after</snippet>',
        html_entity_decode('before &#xE007;&#xE005;+00 123 456789&#xE006;&#xE008; after')
      ),
      array(
        '<snippet>before <marker type="linebreak"/> after</snippet>',
        html_entity_decode('before &#xE004; after')
      ),
      array(
        '<snippet>zum Beispiel als leise <marker type="query_term">Musik</marker>, lautes Hupen</snippet>',
        'zum Beispiel als leise Musik, lautes Hupen'
      )
    );
  }

}

