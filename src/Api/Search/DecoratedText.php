<?php

namespace Papaya\Module\Bing\Api\Search;

use PapayaXmlElement;

class DecoratedText implements \PapayaXmlAppendable {

  const QUERY_TERM = 'query_term';
  const ITALIC = 'italic';
  const LINEBREAK = 'linebreak';
  const PHONE_NUMBER = 'phone_number';
  const ADDRESS = 'address';
  const NON_BREAKING_SPACE = 'non_breaking_space';
  const STRONG = 'strong';
  const LIGHTER = 'lighter';
  const DARKER = 'darker';
  const DELETED = 'deleted';
  const SUBSCRIPT = 'subscript';
  const SUPERSCRIPT = 'superscript';

  private $_text;

  private static $_markers = array(
    "\u{E000}" => array(self::QUERY_TERM, "\u{E001}"),
    "\u{E002}" => array(self::ITALIC, "\u{E003}"),
    "\u{E004}" => array(self::LINEBREAK),
    "\u{E005}" => array(self::PHONE_NUMBER, "\u{E006}"),
    "\u{E007}" => array(self::ADDRESS, "\u{E008}"),
    "\u{E009}" => array(self::NON_BREAKING_SPACE),
    "\u{E00C}" => array(self::STRONG, "\u{E00D}"),
    "\u{E00E}" => array(self::LIGHTER, "\u{E00F}"),
    "\u{E010}" => array(self::DARKER, "\u{E011}"),
    "\u{E012}" => array(self::DELETED, "\u{E013}"),
    "\u{E016}" => array(self::SUBSCRIPT, "\u{E017}"),
    "\u{E018}" => array(self::SUPERSCRIPT, "\u{E019}")
  );
  private static $_replacedMarkers = FALSE;


  public function __construct($text) {
    $this->_text = $text;
    if (!self::$_replacedMarkers && PHP_VERSION_ID < 70000) {
      $markers = [];
      foreach (self::$_markers as $begin => $marker) {
        if (isset($marker[1])) {
          $marker[1] = $this->codePointsToUtf8($marker[1]);
        }
        $markers[$this->codePointsToUtf8($begin)] = $marker;
      }
      self::$_markers = $markers;
    }
  }

  private function codePointsToUtf8($text) {
    return preg_replace_callback(
      '(\\\\u\\{([^}]+)\\})',
      function($match) {
        return html_entity_decode('&#x'.$match[1].';');
      },
      $text
    );
  }

  public function appendTo(PapayaXmlElement $parent) {
    $this->replace($parent,  $this->_text);
  }

  private function next($text, $offset = 0) {
    $found = \preg_match("([\x{E000}-\x{E019}])u", $text, $matches, PREG_OFFSET_CAPTURE, $offset);
    if ($found) {
      return $matches[0];
    }
    return NULL;
  }

  /**
   * @param PapayaXmlElement $parent
   * @param string $text
   */
  private function replace(PapayaXmlElement $parent, $text) {
    $offset = 0;
    while ($next = $this->next($text, $offset)) {
      $before = \substr($text, $offset, $next[1]);
      $parent->appendText($before);
      $offset = $next[1] + \strlen($next[0]);
      if (isset(self::$_markers[$next[0]])) {
        $marker = self::$_markers[$next[0]];
        if (
          isset($marker[1]) &&
          $offset < \strlen($text) &&
          FALSE !== ($end = \strpos($text, $marker[1], $offset))
        ) {
          $between = \substr($text, $offset, $end - $offset);
          $offset = $end + \strlen($marker[1]);
          $node = $parent->appendElement('marker', ['type' => $marker[0]]);
          $this->replace($node, $between);
        } else {
          $parent->appendElement('marker', ['type' => $marker[0]]);
        }
      }
    }
    $parent->appendText(\substr($text, $offset));
  }
}
