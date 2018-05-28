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
        '<snippet>Das &#xE4;u&#xDF;ere <marker type="query_term">Ohr</marker> beginnt mit der Ohrmuschel und endet beim <marker type="query_term">Trommelfell</marker>. ... Wenn doch einmal Wasser ins <marker type="query_term">Ohr</marker> ... Bei empfindlichen <marker type="query_term">Geh&#xF6;rg&#xE4;ngen</marker> nicht ...</snippet>',
        'Das äußere Ohr beginnt mit der Ohrmuschel und endet beim Trommelfell. ... Wenn doch einmal Wasser ins Ohr ... Bei empfindlichen Gehörgängen nicht ...'
      )
    );
  }

}

