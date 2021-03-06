<?php

namespace Papaya\Module\Bing\Api\Search\Message;

use Papaya\Module\Bing\Api\Search\Message;

class EmptyQuery extends Message {

  const IDENTIFIER = 'EmptyQuery';

  public function __construct() {
    parent::__construct(self::IDENTIFIER, self::SEVERITY_WARNING);
  }
}
