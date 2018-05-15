<?php

namespace Papaya\Module\Bing\Api\Search\Error;

use Papaya\Module\Bing\Api\Search\Error;

class EmptyQuery extends Error {

  public function __construct() {
    parent::__construct('EmptyQuery', self::SEVERITY_WARNING);
  }
}
