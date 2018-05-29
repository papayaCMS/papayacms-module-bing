<?php

namespace Papaya\Module\Bing\Api;

class Search {

  private $_endPoint;
  private $_key;
  private $_identifier;
  private $_limit;
  private $_cache;
  private $_expires;
  private $_textDecorations;

  public function __construct($endPoint, $key, $identifier, $limit = 10) {
    $this->_endPoint = $endPoint;
    $this->_key = $key;
    $this->_identifier = $identifier;
    $this->_limit = (int)$limit;
  }

  public function cache(\PapayaCacheService $cache = NULL, $expires = 0) {
    if (NULL !== $cache) {
      $this->_cache = $cache;
      $this->_expires = (int)$expires;
    }
    return $this->_cache;
  }

  public function enableTextDecorations() {
    $this->_textDecorations = TRUE;
  }

  public function disableTextDecorations() {
    $this->_textDecorations = FALSE;
  }

  /**
   * @param string $searchFor
   * @param int $pageIndex
   * @return Search\Message|Search\Result
   */
  public function fetch($searchFor, $pageIndex = 1) {
    if ('' === trim($searchFor)) {
      return new Search\Message\EmptyQuery();
    }
    if ('' !== trim($this->_identifier)) {

      $offset =  $pageIndex * $this->_limit - $this->_limit;
      $url = sprintf(
        '%s?q=%s&customconfig=%s&count=%d&offset=%d&textDecorations=%s',
        $this->_endPoint,
        urlencode($searchFor),
        urlencode($this->_identifier),
        $this->_limit,
        $offset,
        $this->_textDecorations ? 'true' : 'false'
      );
      $cacheParameters = array(
        $this->_identifier,
        $searchFor,
        $this->_limit,
        $offset,
        $this->_textDecorations ? 'true' : 'false'
      );

      $response = NULL;
      $cache = NULL;
      $hasCache = $this->_expires > 0 && ($cache = $this->cache()) && $cache->verify(TRUE);
      $isCached = FALSE;
      if (
        $hasCache &&
        ($response = $cache->read('BING_CUSTOM_SEARCH', $this->_identifier, $cacheParameters, $this->_expires))
      ) {
        $isCached = TRUE;
      } else {
        $options = array(
          'http' => array(
            'method' => 'GET',
            'header' => 'Ocp-Apim-Subscription-Key: '.$this->_key."\r\n"
          )
        );
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
      }
      if ($response) {
        $result = json_decode($response, JSON_OBJECT_AS_ARRAY);
        $type = isset($result['_type']) ? $result['_type'] : '';
        switch ($type) {
        case 'SearchResponse':
          if ($hasCache) {
            $cache->write(
              'BING_CUSTOM_SEARCH', $this->_identifier, $cacheParameters, $response, $this->_expires
            );
          }
          return new Search\Result($result, $isCached);
        }
      } elseif (
        $hasCache &&
        (
          $response = $cache->read(
            'BING_CUSTOM_SEARCH', $this->_identifier, $cacheParameters, $this->_expires + 100
          )
        )
      ) {
        $result = json_decode($response, JSON_OBJECT_AS_ARRAY);
        $type = isset($result['_type']) ? $result['_type'] : '';
        switch ($type) {
        case 'SearchResponse':
          return new Search\Result($result, TRUE);
        }
      }
    }
    return new Search\Message\TechnicalError();
  }

}

