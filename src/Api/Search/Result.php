<?php

namespace Papaya\Module\Bing\Api\Search;

class Result implements \IteratorAggregate, \Countable {

  private $_response;
  private $_pages;
  private $_fromCache;
  private $_messages;

  public function __construct($response, $fromCache = FALSE) {
    $this->_response = $response;
    $this->_fromCache = (bool)$fromCache;
  }

  public function isFromCache() {
    return $this->_fromCache;
  }

  public function isAlteredQuery() {
    return isset($this->_response['queryContext']['alteredQuery']);
  }

  public function getMessages() {
    if (NULL === $this->_messages) {
      $this->_messages = [];
      if ($this->isAlteredQuery()) {
        $this->_messages[] = new Message\AlteredQuery(
          $this->getQuery(), $this->getOriginalQuery()
        );
      }
      if (count($this) < 1) {
        $this->_messages[] = new Message\EmptyQuery();
      }
    }
    return $this->_messages;
  }

  public function getEstimatedMatches() {
    return isset($this->_response['webPages']['totalEstimatedMatches'])
      ? (int)$this->_response['webPages']['totalEstimatedMatches']
      : 0;
  }

  public function getQuery() {
    return isset($this->_response['queryContext']['alteredQuery'])
      ? $this->_response['queryContext']['alteredQuery']
      : $this->getOriginalQuery();
  }

  public function getOriginalQuery() {
    return isset($this->_response['queryContext']['originalQuery'])
      ? $this->_response['queryContext']['originalQuery']
      : '';
  }

  private function getPages() {
    if (NULL === $this->_pages) {
      $this->_pages = [];
      if (isset($this->_response['webPages']['value']) && is_array($this->_response['webPages']['value'])) {
        foreach ($this->_response['webPages']['value'] as $page) {
          $this->_pages[] = [
            'url' => $page['url'],
            'title' => $page['name'],
            'snippet' => $page['snippet'],
            'fixed_position' => $page['fixedPosition']
          ];
        }
      }
    }
    return $this->_pages;
  }

  public function getIterator() {
    return new \ArrayIterator($this->getPages());
  }

  public function count() {
    return \count($this->getPages());
  }

}

