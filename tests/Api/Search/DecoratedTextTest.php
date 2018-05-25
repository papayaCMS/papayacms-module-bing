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
        "before \u{E000}term\u{E001} after"
      ),
      array(
        '<snippet>before <marker type="address"><marker type="phone_number">+00 123 456789</marker></marker> after</snippet>',
        "before \u{E007}\u{E005}+00 123 456789\u{E006}\u{E008} after"
      ),
      array(
        '<snippet>before <marker type="linebreak"/> after</snippet>',
        "before \u{E004} after"
      ),
      array(
        '<snippet>zum Beispiel als leise <marker type="query_term">Musik</marker>, lautes Hupen</snippet>',
        'zum Beispiel als leise Musik, lautes Hupen'
      )
    );
  }

}

