<?php

namespace Papaya\Module\Bing\Api;

class Search {

  private $_endPoint;
  private $_key;
  private $_identifier;
  private $_limit;

  public function __construct($endPoint, $key, $identifier, $limit = 10) {
    $this->_endPoint = $endPoint;
    $this->_key = $key;
    $this->_identifier = $identifier;
    $this->_limit = (int)$limit;
  }

  public function fetch($searchFor, $pageIndex = 1) {
    $options = array(
      'http' => array(
        'method' => 'GET',
        'header' => 'Ocp-Apim-Subscription-Key: '.$this->_key."\r\n"
      )
    );
    $context = stream_context_create($options);
    $offset =  $pageIndex * $this->_limit - $this->_limit;
    $url = sprintf(
      '%s?q=%s&customconfig=%s&count=%d&offset=%d',
      $this->_endPoint,
      urlencode($searchFor),
      urlencode($this->_identifier),
      $this->_limit,
      $offset
    );
    $response = file_get_contents($url, false, $context);
    if ($response) {
      $result = json_decode($response, JSON_OBJECT_AS_ARRAY);
      $type = isset($result['_type']) ? $result['_type'] : '';
      switch ($type) {
      case 'SearchResponse':
        return new Search\Result($result);
      }
    }
    return NULL;
  }

}

