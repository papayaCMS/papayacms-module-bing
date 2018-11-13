<?php

namespace Papaya\Module\Bing\Api\Search\Message;

use Papaya\Module\Bing\Api\Search\Message;

class TechnicalError extends Message {

  const IDENTIFIER = 'TechnicalError';

  private $_description;

  public function __construct($description = '') {
    $this->_description = $description;
    parent::__construct(self::IDENTIFIER, self::SEVERITY_WARNING);
  }

  public function getDescription() {
    return $this->_description;
  }

  public function appendTo(\PapayaXmlElement $parent) {
    $message = parent::appendTo($parent);
    $message->appendChild(
      $message->ownerDocument->createComment($this->getDescription())
    );
    return $message;
  }
}
