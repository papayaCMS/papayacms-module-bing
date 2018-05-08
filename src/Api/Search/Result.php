<?php

namespace Papaya\Module\Bing\Api\Search;

class Result implements \IteratorAggregate, \Countable {

  private $_response;
  private $_pages;

  public function __construct($response) {
    $this->_response = $response;
  }

  public function getEstimatedMatches() {
    return isset($this->_response['webPages']['totalEstimatedMatches'])
      ? (int)$this->_response['webPages']['totalEstimatedMatches']
      : 0;
  }

  public function getQuery() {
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
      return $this->_pages;
    }

  }

  public function getIterator() {
    return new \ArrayIterator($this->getPages());
  }

  public function count() {
    return \count($this->getPages());
  }

}

