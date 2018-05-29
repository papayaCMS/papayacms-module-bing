<?php

namespace Papaya\Module\Bing\Api\Search;

abstract class Message implements \PapayaXmlAppendable {

  const SEVERITY_INFO = 'info';
  const SEVERITY_WARNING = 'warning';
  const SEVERITY_ERROR = 'error';

  private $_severity;
  private $_identifier;
  private $_userMessage;

  public function __construct($identifier, $severity = self::SEVERITY_ERROR) {
    $this->_identifier = $identifier;
    $this->_severity = $severity;
  }

  public function setUserMessage($xmlString) {
    $this->_userMessage = $xmlString;
  }

  public function getUserMessage() {
    return $this->_userMessage;
  }

  public function appendTo(\PapayaXmlElement $parent) {
    $message = $parent->appendElement(
      'message',
      array(
        'severity' => $this->getSeverity(),
        'identifier' => $this->getIdentifier()
      )
    );
    if ($userMessage = $this->getUserMessage()) {
      $message->appendXml($userMessage);
    }
    return $message;
  }

  public function getSeverity() {
    return $this->_severity;
  }

  public function getIdentifier() {
    return $this->_identifier;
  }
}
