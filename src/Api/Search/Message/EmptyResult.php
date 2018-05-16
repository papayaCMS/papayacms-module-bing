<?php

namespace Papaya\Module\Bing\Api\Search\Message;

use Papaya\Module\Bing\Api\Search\Message;

class EmptyResult extends Message {

  const IDENTIFIER = 'EmptyResult';

  public function __construct() {
    parent::__construct(self::IDENTIFIER, self::SEVERITY_INFO);
  }
}
