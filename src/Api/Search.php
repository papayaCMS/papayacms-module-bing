<?php

class PapayaModuleBingApiSearch extends PapayaObjectInteractive implements PapayaXmlAppendable {

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

  public function appendTo(PapayaXmlElement $parent) {
    $searchFor = $this->parameters()->get('q', '');
    $pageIndex = max(1, $this->parameters()->get('q_page', 1));
    $offset =  $pageIndex * $this->_limit - $this->_limit;
    if ($searchFor !== '') {
      $result = $this->fetch(
        $searchFor, $this->_limit, $offset
      );
      $searchNode = $parent->appendElement('search-result');
      if ($result instanceof PapayaModuleBingApiSearchResult) {
        $searchNode->setAttribute('for', $result->getQuery());
        $urlsNode = $searchNode->appendElement('urls');
        $urlsNode->setAttribute('estimated-total', $result->getEstimatedMatches());
        $urlsNode->setAttribute('offset', $offset);
        foreach ($result as $url) {
          $urlNode = $urlsNode->appendElement('url');
          $urlNode->setAttribute('href', $url['url']);
          $urlNode->setAttribute('fixed-position', $url['fixed_position'] ? 'true' : 'false');
          $urlNode->appendElement('title', [], $url['title']);
          $urlNode->appendElement('snippet', [], $url['snippet']);
        }
        $searchNode->append(new PapayaUiPagingCount('q_page', $pageIndex, $result->getEstimatedMatches()));
      }
    }
  }

  public function fetch($searchFor, $limit = 0, $offset = 0) {
    $options = array(
      'http' => array(
        'method' => 'GET',
        'header' => 'Ocp-Apim-Subscription-Key: '.$this->_key."\r\n"
      )
    );
    $context = stream_context_create($options);
    $url = sprintf(
      '%s?q=%s&customconfig=%s&count=%d&offset=%d',
      $this->_endPoint,
      urlencode($searchFor),
      urlencode($this->_identifier),
      $limit,
      $offset
    );
    $response = file_get_contents($url, false, $context);
    if ($response) {
      $result = json_decode($response, JSON_OBJECT_AS_ARRAY);
      $type = isset($result['_type']) ? $result['_type'] : '';
      switch ($type) {
      case 'SearchResponse':
        return new PapayaModuleBingApiSearchResult($result);
      }
    }
    return NULL;
  }

}

