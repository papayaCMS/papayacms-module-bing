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

  private static $_defaults = [
    'bing_result_limit' => 10,
    'search_term_parameter' => 'q',
    'search_term_parameter_fallbacks' => '',
    'search_term_options' => Api\Search::QUERY_LOWERCASE,
    'bing_result_cache_time' => 0,
    'result_append_teasers' => FALSE,
    'result_decorate_text' => TRUE,
    'message_empty_query' => 'Nothing to search for.',
    'message_altered_query' => 'Corrected search query.',
    'message_empty_result' => 'Nothing found.',
    'message_technical_error' => 'Technical error. Can not search.'
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

    $searchParameter = $this->content()->get('search_term_parameter', self::$_defaults['search_term_parameter']);
    $searchParameters = preg_split(
      '(\s+)', trim($this->content()->get('search_term_parameter_fallbacks', ''))
    );
    array_unshift($searchParameters, $searchParameter);
    foreach ($searchParameters as $parameterName) {
      if (trim($parameterName) === '') {
        continue;
      }
      $searchFor = $this->parameters()->get($parameterName, '');
      if (trim($searchFor) !== '') {
        break;
      }
    }
    $pageIndex = max(1, $this->parameters()->get('q_page', 1));

    $searchResult = $this->searchApi()->fetch($searchFor, $pageIndex);
    $searchNode = $parent->appendElement('search');
    if ($searchResult instanceof Api\Search\Result) {
      $searchNode->setAttribute('term', $searchResult->getQuery());
      foreach ($searchResult->getMessages() as $message) {
        $this->appendMessageTo($searchNode, $message);
      }
      $searchNode->setAttribute('cached', $searchResult->isFromCache() ? 'true' : 'false');
      $urlsNode = $searchNode->appendElement('urls');
      $urlsNode->setAttribute('estimated-total', $searchResult->getEstimatedMatches());
      $urlsNode->setAttribute('count', count($searchResult));

      $addTeasers = $this->content()->get('result_append_teasers', self::$_defaults['result_append_teasers']);
      $teasers = [];
      if ($addTeasers) {
        $teasers = $this->createTeasers(new \PapayaIteratorArrayMapper($searchResult, 'url'));
      }
      foreach ($searchResult as $url) {
        $urlNode = $urlsNode->appendElement('url');
        $urlNode->setAttribute('href', $url['url']);
        $urlNode->setAttribute('last-crawled', \PapayaUtilDate::timestampToString($url['last_crawled']));
        $urlNode->setAttribute('fixed-position', $url['fixed_position'] ? 'true' : 'false');
        $urlNode->appendElement('title')->append(new Api\Search\DecoratedText($url['title']));
        $urlNode->appendElement('snippet')->append(new Api\Search\DecoratedText($url['snippet']));
        if ($addTeasers && isset($teasers[$url['url']])) {
          $teaser = $teasers[$url['url']];
          /** @var \PapayaUiContentPage $page */
          $page = $teaser['page'];
          $view = $teaser['view'];
          $page->appendQuoteTo(
            $urlNode, array('query_string' => $teaser['query_string']), $view
          );
        }
      }
      $paging = new \PapayaUiPagingCount('q_page', $pageIndex, $searchResult->getEstimatedMatches());
      $paging->setItemsPerPage(
        $this->content()->get('bing_result_limit', self::$_defaults['bing_result_limit'])
      );
      $paging->reference()->setParameters(
        array(
          $searchParameter => $searchResult->getQuery()
        )
      );
      $searchNode->append($paging);
    } elseif ($searchResult instanceof Api\Search\Message) {
      $this->appendMessageTo($searchNode, $searchResult);
    }

    $parent->append($filters);
  }

  private function createTeasers($urls) {
    $result = [];
    $viewIds = [];
    foreach ($urls as $url) {
      $request = new \PapayaRequest();
      $request->load(new \PapayaUrl($url));
      if ($request->pageId > 0 && $request->languageId > 0) {
        $result[$url] = array(
          'page' => $page = new \PapayaUiContentPage(
            $request->pageId, $request->languageId, TRUE
          ),
          'page_id' => $request->pageId,
          'language_id' => $request->languageId,
          'view_id' => $viewId = $page->translation()->viewId,
          'view' => NULL,
          'query_string' => $request->url->getQuery(),
        );
        $viewIds[] = $viewId;
      }
    }
    $views = new \PapayaContentViewConfigurations();
    $views->papaya($this->papaya());
    $viewModeId = $this->papaya()->request->mode()->id;
    $filter = ['id' => array_unique($viewIds), 'mode_id' => $viewModeId, 'output'];
    if ($views->load($filter)) {
      foreach ($result as &$item) {
        if ($view = $views->offsetGet([$item['view_id'], $viewModeId, 'output'])) {
          $item['view'] = $view;
        }
      }
    }
    return $result;
  }

  private function appendMessageTo(\PapayaXmlElement $parent, Api\Search\Message $message) {
    $identifiers = array(
      Api\Search\Message\EmptyQuery::IDENTIFIER => 'message_empty_query',
      Api\Search\Message\AlteredQuery::IDENTIFIER => 'message_altered_query',
      Api\Search\Message\EmptyResult::IDENTIFIER => 'message_empty_result',
      Api\Search\Message\TechnicalError::IDENTIFIER => 'message_technical_error'
    );
    /**
     * @var \PapayaXMLElement $messageNode
     */
    if (isset($identifiers[$message->getIdentifier()])) {
      $contentField = $identifiers[$message->getIdentifier()];
      $message->setUserMessage(
        $this->content()->get($contentField, self::$_defaults[$contentField])
      );
    }
    $parent->append($message);
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
        $this->content()->get('bing_result_limit', self::$_defaults['bing_result_limit']),
        $this->content()->get('bing_result_cache_time', self::$_defaults['bing_result_cache_time'])
      );
      $this->_searchApi->enableTextDecorations();
      $this->_searchApi->setSearchStringOptions(
        $this->content()->get('search_term_options', self::$_defaults['search_term_options'])
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
    $general = new \PapayaAdministrationPluginEditorDialog($content);
    $dialog = $general->dialog();
    $dialog->fields[] = $group = new \PapayaUiDialogFieldGroup(
      new \PapayaUiStringTranslated('Api')
    );
    $group->fields[] = new \PapayaUiDialogFieldInput(
      new \PapayaUiStringTranslated('Bing configuration id'),
      'bing_configuration_id'
    );
    $group->fields[] = new \PapayaUiDialogFieldInputNumber(
      new \PapayaUiStringTranslated('Cache Time'),
      'bing_result_cache_time',
      self::$_defaults['bing_result_cache_time'],
      FALSE,
      NULL,
      10
    );
    $dialog->fields[] = $group = new \PapayaUiDialogFieldGroup(
      new \PapayaUiStringTranslated('Search Parameter')
    );
    $group->fields[] = new \PapayaUiDialogFieldInput(
      new \PapayaUiStringTranslated('Name'),
      'search_term_parameter',
      20,
      self::$_defaults['search_term_parameter']
    );
    $group->fields[] = $field = new \PapayaUiDialogFieldSelectBitmask(
      new \PapayaUiStringTranslated('Options'),
      'search_term_options',
      [
        Api\Search::QUERY_LOWERCASE => new \PapayaUiStringTranslated('Lowercase')
      ],
      FALSE
    );
    $field->setDefaultValue(self::$_defaults['search_term_options']);
    $group->fields[] = $field = new \PapayaUiDialogFieldTextarea(
      new \PapayaUiStringTranslated('Fallback Parameters'),
      'search_term_parameter_fallbacks',
      4,
      self::$_defaults['search_term_parameter_fallbacks']
    );
    $dialog->fields[] = $group = new \PapayaUiDialogFieldGroup(
      new \PapayaUiStringTranslated('Result')
    );
    $group->fields[] = new \PapayaUiDialogFieldInputNumber(
      new \PapayaUiStringTranslated('Items per page'),
      'bing_result_limit',
      self::$_defaults['bing_result_limit'],
      TRUE,
      2,
      3
    );
    $group->fields[] = new \PapayaUiDialogFieldInputCheckbox(
      new \PapayaUiStringTranslated('Add page teasers'),
      'result_append_teasers',
      self::$_defaults['result_append_teasers']
    );
    $group->fields[] = new \PapayaUiDialogFieldInputCheckbox(
      new \PapayaUiStringTranslated('Add text decorations'),
      'result_decorate_text',
      self::$_defaults['result_decorate_text']
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

    $messages = new \PapayaAdministrationPluginEditorDialog($content);
    $dialog = $messages->dialog();

    $dialog->fields[] = $field = new \PapayaUiDialogFieldTextareaRichtext(
      new \PapayaUiStringTranslated('Empty Query'),
      'message_empty_query',
      5,
      self::$_defaults['message_empty_query'],
      new \PapayaFilterXml(),
      \PapayaUiDialogFieldTextareaRichtext::RTE_SIMPLE
    );
    $dialog->fields[] = $field = new \PapayaUiDialogFieldTextareaRichtext(
      new \PapayaUiStringTranslated('Altered Query'),
      'message_altered_query',
      5,
      self::$_defaults['message_altered_query'],
      new \PapayaFilterXml(),
      \PapayaUiDialogFieldTextareaRichtext::RTE_SIMPLE
    );
    $field->setHint(
      new \PapayaUiStringTranslated(
        'Supports placeholders: %s', array('{%ORIGINAL_QUERY%}, {%ALTERED_QUERY%}')
      )
    );
    $dialog->fields[] = $field = new \PapayaUiDialogFieldTextareaRichtext(
      new \PapayaUiStringTranslated('Empty Result'),
      'message_empty_result',
      5,
      self::$_defaults['message_empty_result'],
      new \PapayaFilterXml(),
      \PapayaUiDialogFieldTextareaRichtext::RTE_SIMPLE
    );
    $dialog->fields[] = $field = new \PapayaUiDialogFieldTextareaRichtext(
      new \PapayaUiStringTranslated('Technical Error'),
      'message_technical_error',
      5,
      self::$_defaults['message_technical_error'],
      new \PapayaFilterXml(),
      \PapayaUiDialogFieldTextareaRichtext::RTE_SIMPLE
    );

    $editor = new \PapayaAdministrationPluginEditorGroup($content, 'tt/editor-index');
    $editor->add(
      $general,
      new \PapayaUiStringTranslated('General'),
      'categories-content'
    );
    $editor->add(
      $messages,
      new \PapayaUiStringTranslated('Messages'),
      'items-message'
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
