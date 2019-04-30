<?php

namespace Papaya\Module\Bing\Api;

class Search {

  const QUERY_LOWERCASE = 1;

  private $_endPoint;
  private $_key;
  private $_identifier;
  private $_limit;
  private $_cache;
  private $_expires;
  private $_textDecorations;
  private $_searchStringOptions;
  private $_disableSSLPeerVerification;

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

  public function enableSSLPeerVerification() {
    $this->_disableSSLPeerVerification = FALSE;
  }

  public function disableSSLPeerVerification() {
    $this->_disableSSLPeerVerification = TRUE;
  }

  public function setSearchStringOptions($options = 0) {
    $this->_searchStringOptions = (int)$options;
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
    if ('' === trim($this->_endPoint)) {
      return new Search\Message\TechnicalError('No API endpoint defined.');
    }
    if ('' === trim($this->_key)) {
      return new Search\Message\TechnicalError('No API key defined.');
    }
    if ('' === trim($this->_identifier)) {
      return new Search\Message\TechnicalError('No custom search identifier defined.');
    }
    if (\PapayaUtilBitwise::inBitmask(self::QUERY_LOWERCASE, $this->_searchStringOptions)) {
      $searchFor = (string)\PapayaUtilStringUtf8::toLowerCase($searchFor);
    }
    $offset = $pageIndex * $this->_limit - $this->_limit;
    if ($this->_limit < 50) {
      // calculate the maximum results splitable into pages that fit into one api request
      $fetchLimit = floor(50 / $this->_limit) * $this->_limit;
    } else {
      $fetchLimit = 50;
    }
    // round offset
    $fetchOffset = floor($offset / $fetchLimit) * $fetchLimit;
    // offset of resuls inside the api result
    $resultOffset = $offset - $fetchOffset;
    $url = sprintf(
      '%s?q=%s&customconfig=%s&count=%d&offset=%d&textDecorations=%s',
      $this->_endPoint,
      urlencode($searchFor),
      urlencode($this->_identifier),
      $fetchLimit,
      $fetchOffset,
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
          'header' => 'Ocp-Apim-Subscription-Key: '.$this->_key."\r\n",
          'timeout ' => 3,
          'ignore_errors' => '1'
        )
      );
      if ($this->_disableSSLPeerVerification) {
        $options['ssl'] = [
          'verify_peer' => FALSE,
          'verify_peer_name' => FALSE,
        ];
      }
      $context = stream_context_create($options);
      try {
        $response = file_get_contents($url, FALSE, $context);
      } catch (\Exception $e) {
        return new Search\Message\TechnicalError('Request failed:'.$e->getMessage());
      } catch (\Throwable $e) {
        return new Search\Message\TechnicalError('Request failed:'.$e->getMessage());
      }
    }
    if (FALSE !== $response) {
      $result = json_decode($response, JSON_OBJECT_AS_ARRAY);
      $type = isset($result['_type']) ? $result['_type'] : '';
      switch ($type) {
      case 'ErrorResponse':
        $errors = \implode(
          ', ',
          array_map(
            function($error) {
              if (!is_array($error)) {
                return NULL;
              }
              return \sprintf(
                '%s - %s (%s)',
                \PapayaUtilArray::get($error, 'code', ''),
                \PapayaUtilArray::get($error, 'message', ''),
                \PapayaUtilArray::get($error, 'parameter', '')
              );
            },
            isset($result['errors']) && \is_array($result['errors']) ? $result['errors'] : []
          )
        );
        return new Search\Message\TechnicalError('API responded with error(s): '.$errors);
      case 'SearchResponse':
        if ($hasCache) {
          $cache->write(
            'BING_CUSTOM_SEARCH', $this->_identifier, $cacheParameters, $response, $this->_expires
          );
        }
        return new Search\Result($result, $isCached, $resultOffset, $this->_limit);
      default:
        if (isset($result['statusCode'])) {
          return new Search\Message\TechnicalError(
            'API request failed: '.
            \PapayaUtilArray::get($result, 'statusCode', 0).' - '.
            \PapayaUtilArray::get($result, 'message', 0)
          );
        }
        if (isset($result['error'])) {
          return new Search\Message\TechnicalError(
            'API request failed: '.
            \PapayaUtilArray::get($result['error'], 'code', 0).' - '.
            \PapayaUtilArray::get($result['error'], 'message', 0)
          );
        }
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
    return new Search\Message\TechnicalError(
      'API request failed'.(isset($http_response_header[0]) ? ': '.$http_response_header[0] : '.')
    );
  }

}

