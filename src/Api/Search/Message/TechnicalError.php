<?php

namespace Papaya\Module\Bing\Api\Search\Message;

use Papaya\Module\Bing\Api\Search\Message;

class TechnicalError extends Message {

  const IDENTIFIER = 'TechnicalError';

  public function __construct() {
    parent::__construct(self::IDENTIFIER, self::SEVERITY_WARNING);
  }
}
