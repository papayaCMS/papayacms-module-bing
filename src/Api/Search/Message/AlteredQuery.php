<?php

namespace Papaya\Module\Bing\Api\Search\Message;

use Papaya\Module\Bing\Api\Search\Message;

class AlteredQuery extends Message {

  const IDENTIFIER = 'AlteredQuery';

  private $_original;
  private $_altered;

  public function __construct($altered, $original) {
    $this->_altered = $altered;
    $this->_original = $original;
    parent::__construct(self::IDENTIFIER, self::SEVERITY_INFO);
  }

  public function appendTo(\PapayaXmlElement $parent) {
    $messageNode = parent::appendTo($parent);
    $messageNode->setAttribute('altered-query', $this->_altered);
    $messageNode->setAttribute('original-query', $this->_original);
    return $messageNode;
  }
}
