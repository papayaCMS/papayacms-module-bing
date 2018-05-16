<?php

namespace Papaya\Module\Bing\Api\Search\Error;

use Papaya\Module\Bing\Api\Search\Error;

class EmptyQuery extends Error {

  const IDENTIFIER = 'EmptyQuery';

  public function __construct() {
    parent::__construct(self::IDENTIFIER, self::SEVERITY_WARNING);
  }
}
