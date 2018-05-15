<?php

namespace Papaya\Module\Bing;

class Page
  extends
    \PapayaObjectInteractive
  implements
    \PapayaPluginConfigurable,
    \PapayaPluginAppendable,
    \PapayaPluginQuoteable,
    \PapayaPluginEditable,
    \PapayaPluginCacheable {

  use
    \PapayaPluginConfigurableAggregation,
    \PapayaPluginEditableContentAggregation,
    \PapayaPluginCacheableAggregation,
    \PapayaPluginFilterAggregation;

  const API_GUID = '8d56ab8c4c39f5d086c467cbf96ed27e';

  /**
   * @var \Papaya\Module\Bing\Api\Search
   */
  private $_searchApi;

  private $_defaults = [
    'bing_result_limit' => 10,
    'search_term_parameter' => 'q',
    'bing_result_cache_time' => 0
  ];

  public function __construct($page) {
    $this->_page = $page;
  }

  /**
   * Append the teaser output xml to the DOM.
   *
   * @see \PapayaXmlAppendable::appendTo()
   * @param \PapayaXmlElement $parent
   * @return NULL|\PapayaXmlElement|void
   */
  public function appendQuoteTo(\PapayaXmlElement $parent) {
    $parent->appendElement('title', [], $this->content()->get('title', ''));
    $parent->appendElement('text')->appendXml($this->content()->get('teaser', ''));
  }

  /**
   * Append the page output xml to the DOM.
   *
   * @see PapayaXmlAppendable::appendTo()
   * @param \PapayaXmlElement $parent
   */
  public function appendTo(\PapayaXmlElement $parent) {
    $filters = $this->filters();
    $filters->prepare(
      $this->content()->get('text', ''),
      $this->configuration()
    );
    $parent->appendElement('title', [], $this->content()->get('title', ''));
    $parent->appendElement('teaser')->appendXml($this->content()->get('teaser', ''));
    $parent->appendElement('text')->appendXml(
      $filters->applyTo($this->content()->get('text', ''))
    );

    $searchParameter = $this->content()->get('search_term_parameter', $this->_defaults['search_term_parameter']);
    $searchFor = $this->parameters()->get($searchParameter, '');
    $pageIndex = max(1, $this->parameters()->get('q_page', 1));
    $pageCount = $this->content()->get('bing_result_limit', $this->_defaults['bing_result_limit']);
    $searchResult = $this->searchApi()->fetch($searchFor, $pageIndex);
    $searchNode = $parent->appendElement('search');
    if ($searchResult instanceof Api\Search\Result) {
      $searchNode->setAttribute('term', $searchResult->getQuery());
      $searchNode->setAttribute('cached', $searchResult->isFromCache() ? 'true' : 'false');
      $urlsNode = $searchNode->appendElement('urls');
      $urlsNode->setAttribute('estimated-total', $searchResult->getEstimatedMatches());
      $urlsNode->setAttribute('offset', ($pageIndex - 1) * $pageCount);
      foreach ($searchResult as $url) {
        $urlNode = $urlsNode->appendElement('url');
        $urlNode->setAttribute('href', $url['url']);
        $urlNode->setAttribute('fixed-position', $url['fixed_position'] ? 'true' : 'false');
        $urlNode->appendElement('title', [], $url['title']);
        $urlNode->appendElement('snippet', [], $url['snippet']);
      }
      $paging = new \PapayaUiPagingCount('q_page', $pageIndex, $searchResult->getEstimatedMatches());
      $paging->reference()->setParameters(array($searchParameter => $searchFor));
      $searchNode->append($paging);
    }

    $parent->append($filters);
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

  /**
   * The editor is used to change the stored data in the administration interface.
   *
   * In this case it the editor creates an dialog from a field definition.
   *
   * @see PapayaPluginEditableContent::editor()
   *
   * @param \PapayaPluginEditableContent $content
   * @return \PapayaPluginEditor
   * @throws \UnexpectedValueException
   */
  public function createEditor(
    \PapayaPluginEditableContent $content
  ) {
    $editor = new \PapayaAdministrationPluginEditorDialog($content);
    $dialog = $editor->dialog();
    $dialog->fields[] = new \PapayaUiDialogFieldInput(
      new \PapayaUiStringTranslated('Search term parameter'),
      'search_term_parameter',
      20,
      $this->_defaults['search_term_parameter']
    );
    $dialog->fields[] = new \PapayaUiDialogFieldInput(
      new \PapayaUiStringTranslated('Bing configuration id'),
      'bing_configuration_id'
    );
    $dialog->fields[] = new \PapayaUiDialogFieldInputNumber(
      new \PapayaUiStringTranslated('Items per page'),
      'bing_result_limit',
      $this->_defaults['bing_result_limit'],
      TRUE,
      2,
      3
    );
    $dialog->fields[] = new \PapayaUiDialogFieldInputNumber(
      new \PapayaUiStringTranslated('Api Cache Time'),
      'bing_result_cache_time',
      $this->_defaults['bing_result_cache_time'],
      FALSE,
      NULL,
      10
    );
    $dialog->fields[] = $group = new \PapayaUiDialogFieldGroup(
      new \PapayaUiStringTranslated('Texts')
    );
    $group->fields[] = $field = new \PapayaUiDialogFieldInput(
      new \PapayaUiStringTranslated('Title'),
      'title',
      400
    );
    $field->setMandatory(TRUE);
    $group->fields[] = $field = new \PapayaUiDialogFieldTextareaRichtext(
      new \PapayaUiStringTranslated('Teaser'),
      'teaser',
      5,
      '',
      new \PapayaFilterXml(),
      \PapayaUiDialogFieldTextareaRichtext::RTE_SIMPLE
    );
    $group->fields[] = $field = new \PapayaUiDialogFieldTextareaRichtext(
      new \PapayaUiStringTranslated('Text'),
      'text',
      15,
      '',
      new \PapayaFilterXml()
    );
    $editor->papaya($this->papaya());
    return $editor;
  }

  /**
   * Define the cache definition parameters for the output.
   *
   * @return \PapayaCacheIdentifierDefinition
   */
  public function createCacheDefinition() {
    return new \PapayaCacheIdentifierDefinitionUrl();
  }
}
