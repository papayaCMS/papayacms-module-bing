<?php

namespace Papaya\Module\Bing\Api\Search\Error;

use Papaya\Module\Bing\Api\Search\Error;

class TechnicalError extends Error {

  const IDENTIFIER = 'TechnicalError';

  public function __construct() {
    parent::__construct(self::IDENTIFIER, self::SEVERITY_WARNING);
  }
}
