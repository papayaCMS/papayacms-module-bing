<?php

namespace Papaya\Module\Bing;

class BoxRelated
  extends
    \PapayaObject
  implements
    \PapayaPluginAppendable,
    \PapayaPluginEditable {

  use \PapayaPluginEditableContentAggregation;

  const API_GUID = '8d56ab8c4c39f5d086c467cbf96ed27e';

  const MODE_PAGE_TITLE = 'title';
  const MODE_PAGE_METADATA = 'metadata';
  const MODE_PAGE_XPATH = 'xpath';

  /**
   * @var Api\Search
   */
  private $_searchApi;

  private $_defaults = [
    'bing_result_limit' => 10,
    'output_result_limit' => 5,
    'bing_result_cache_time' => 0,
    'search_term_parameter' => 'q',
    'search_term_source_xpath' => 'string(//topic/title)'
  ];

  private $_bootstrap;


  /**
   * Append the page output xml to the DOM.
   *
   * @see PapayaXmlAppendable::appendTo()
   * @param \PapayaXmlElement $parent
   */
  public function appendTo(\PapayaXmlElement $parent) {
    $searchFor = $this->getSearchFor();
    $reference = $this->papaya()->pageReferences->get(
      $this->papaya()->request->languageId,
      $this->content()->get('search_page_id', '')
    );
    $reference->getParameters()->set(
      $this->content()->get(
        'search_term_parameter', $this->_defaults['search_term_parameter']
      ),
      $searchFor
    );
    $searchResult = $this->searchApi()->fetch($searchFor);
    $searchNode = $parent->appendElement(
      'search',
      array('href' => (string)$reference)
    );
    if ($searchResult instanceof Api\Search\Result) {
      $searchNode->setAttribute('term', $searchResult->getQuery());
      $searchNode->setAttribute('cached', $searchResult->isFromCache() ? 'true' : 'false');
      $urlsNode = $searchNode->appendElement('urls');
      foreach ($this->filterUrls($searchResult) as $url) {
        $urlNode = $urlsNode->appendElement('url');
        $urlNode->setAttribute('href', $url['url']);
        $urlNode->setAttribute('fixed-position', $url['fixed_position'] ? 'true' : 'false');
        $urlNode->appendElement('title', [], $url['title']);
        $urlNode->appendElement('snippet', [], $url['snippet']);
      }
    } elseif ($searchResult instanceof Api\Search\Error) {
      $searchNode->append($searchResult);
    }
  }

  private function filterUrls($searchResult) {
    $currentUrl = clone $this->papaya()->request->getUrl();
    $currentUrl->setQuery('');
    $currentHref = $currentUrl->getUrl();
    return new \LimitIterator(
      new \PapayaIteratorFilterCallback(
        $searchResult,
        function($url) use ($currentHref) {
          return $url['url'] !== $currentHref && FALSE === strpos($url['url'], $currentHref.'?');
        }
      ),
      0,
      $this->content()->get('output_result_limit', $this->_defaults['output_result_limit'])
    );
  }

  private function getSearchFor() {
    $searchFor = '';
    switch ($this->content()->get('search_term_source_mode')) {
    case self::MODE_PAGE_XPATH :
      $searchFor = $this->getSearchForFromPageDocument(
        $this->papayaBootstrap()->getPageDocument(),
        $this->content()->get('search_term_source_xpath', $this->_defaults['search_term_source_xpath'])
      );
      break;
    case self::MODE_PAGE_METADATA :
      $metaData = $this->papayaBootstrap()->topic->loadMetaData();
      $keywords = \preg_split('(\s*,\s*)', $metaData['meta_keywords']);
      array_unshift($keywords, $metaData['meta_title']);
      $keywords = array_unique(
        array_filter(
          $keywords,
          function($keyword) {
            return '' !== trim($keyword);
          }
        )
      );
      if (\count($keywords) > 0) {
        $searchFor = implode(
          ' ',
          array_map(
            function ($keyword) {
              return FALSE !== strpos($keyword, ' ') ? '"'.$keyword.'"' : $keyword;
            },
            $keywords
          )
        );
      }
      break;
    case self::MODE_PAGE_TITLE :
    default:
      $searchFor = $this->getSearchForFromPageDocument(
        $this->papayaBootstrap()->getPageDocument(),
        'string(//topic/@title)'
      );
      break;
    }
    return $searchFor;
  }

  private function getSearchForFromPageDocument(\DOMDocument $document, $expression) {
    $errors = new \PapayaXmlErrors();
    return $errors->encapsulate(
      function() use ($document, $expression) {
        $xpath = new \PapayaXmlXpath($document);
        return $xpath->evaluate($expression);
      },
      NULL,
      FALSE
    );
  }

  /**
   * @param Api\Search $searchApi
   * @return Api\Search
   */
  public function searchApi(Api\Search $searchApi = NULL) {
    if (NULL !== $searchApi) {
      $this->_searchApi = $searchApi;
    } elseif (NULL === $this->_searchApi) {
      /** @var API $api */
      $api = $this->papaya()->plugins->get(self::API_GUID, $this);
      $this->_searchApi = $api->createSearchApi(
        $this->content()->get('bing_configuration_id', ''),
        $this->content()->get('bing_result_limit', $this->_defaults['bing_result_limit']),
        $this->content()->get('bing_result_cache_time', $this->_defaults['bing_result_cache_time'])
      );
    }
    return $this->_searchApi;
  }

  public function createEditor(\PapayaPluginEditableContent $content) {
    $editor = new \PapayaAdministrationPluginEditorDialog($content);
    $editor->papaya($this->papaya());
    $dialog = $editor->dialog();
    $dialog->fields[] = new \PapayaUiDialogFieldInput(
      new \PapayaUiStringTranslated('Bing configuration id'),
      'bing_configuration_id'
    );
    $dialog->fields[] = new \PapayaUiDialogFieldInputNumber(
      new \PapayaUiStringTranslated('Api Cache Time'),
      'bing_result_cache_time',
      $this->_defaults['bing_result_cache_time'],
      FALSE,
      NULL,
      10
    );
    $dialog->fields[] = new \PapayaUiDialogFieldInputNumber(
      new \PapayaUiStringTranslated('Items fetch limit'),
      'bing_result_limit',
      $this->_defaults['bing_result_limit'],
      TRUE,
      1,
      2
    );
    $dialog->fields[] = new \PapayaUiDialogFieldInputNumber(
      new \PapayaUiStringTranslated('Items output limit'),
      'output_result_limit',
      $this->_defaults['output_result_limit'],
      TRUE,
      1,
      2
    );
    $dialog->fields[] = $group = new \PapayaUiDialogFieldGroup(
      new \PapayaUiStringTranslated('Search Term')
    );
    $group->fields[] = $field = new \PapayaUiDialogFieldSelectRadio(
      new \PapayaUiStringTranslated('Source'),
      'search_term_source_mode',
      array(
        self::MODE_PAGE_TITLE => 'Page Title',
        self::MODE_PAGE_METADATA => 'Page Metadata',
        self::MODE_PAGE_XPATH => 'Xpath',
      )
    );
    $field->setDefaultValue(self::MODE_PAGE_TITLE);
    $group->fields[] = $field = new \PapayaUiDialogFieldInput(
      new \PapayaUiStringTranslated('Xpath expression'),
      'search_term_source_xpath',
      4000,
      $this->_defaults['search_term_source_xpath']
    );
    $dialog->fields[] = $group = new \PapayaUiDialogFieldGroup(
      new \PapayaUiStringTranslated('Link')
    );
    $group->fields[] = $field = new \PapayaUiDialogFieldInputPage(
      new \PapayaUiStringTranslated('Target Page'),
      'search_page_id',
      NULL,
      TRUE
    );
    $group->fields[] = $field = new \PapayaUiDialogFieldInput(
      new \PapayaUiStringTranslated('Parameter'),
      'search_term_parameter',
      400,
      $this->_defaults['search_term_parameter']
    );
    $field->setMandatory(TRUE);
    return $editor;
  }

  /**
   * @param \papaya_page $papayaBootstrap
   * @return \papaya_page
   */
  public function papayaBootstrap(\papaya_page $papayaBootstrap = NULL) {
    if (NULL !== $papayaBootstrap) {
      $this->_bootstrap = $papayaBootstrap;
    } elseif (NULL === $this->_bootstrap) {
      $this->_bootstrap = $GLOBALS['PAPAYA_PAGE'];
    }
    return $this->_bootstrap;
  }
}
