<?php

namespace Papaya\Module\Bing\Api\Search;

class Error implements \PapayaXmlAppendable {

  const SEVERITY_INFO = 'info';
  const SEVERITY_WARNING = 'warning';
  const SEVERITY_ERROR = 'error';

  private $_severity;
  private $_identifier;

  public function __construct($identifier, $severity = self::SEVERITY_ERROR) {
    $this->_identifier = $identifier;
    $this->_severity = $severity;
  }

  public function appendTo(\PapayaXmlElement $parent) {
    return $parent->appendElement(
      'message',
      array(
        'severity' => $this->getSeverity(),
        'identifier' => $this->getIdentifier()
      )
    );
  }

  public function getSeverity() {
    return $this->_severity;
  }

  public function getIdentifier() {
    return $this->_identifier;
  }
}
