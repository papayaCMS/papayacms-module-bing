<?php

class PapayaModuleBingPage
  extends
  PapayaObjectInteractive
  implements
  PapayaPluginConfigurable,
  PapayaPluginAppendable,
  PapayaPluginQuoteable,
  PapayaPluginEditable,
  PapayaPluginCacheable {

  const DOMAIN_CONNECTOR_GUID = '8ec0c5995d97c9c3cc9c237ad0dc6c0b';

  /**
   * @var PapayaPluginEditableContent
   */
  private $_content;

  /**
   * @var PapayaObjectParameters
   */
  private $_configuration;

  /**
   * @var PapayaCacheIdentifierDefinition
   */
  private $_cacheDefinition;

  /**
   * @var PapayaObject
   */
  private $_owner;

  /**
   * @var PapayaPluginFilterContent
   */
  private $_contentFilters;

  /**
   * @var PapayaModuleBingApiSearch
   */
  private $_searchApi;

  private $_defaults = [
    'bing_api_endpoint' => 'https://api.cognitive.microsoft.com/bingcustomsearch/v7.0/search',
    'bing_result_limit' => 10
  ];

  public function __construct($owner) {
    $this->_owner = $owner;
  }

  /**
   * Append the page output xml to the DOM.
   *
   * @see PapayaXmlAppendable::appendTo()
   * @param PapayaXmlElement $parent
   */
  public function appendTo(PapayaXmlElement $parent) {
    $pageReference =$this->papaya()->pageReferences->get(
      $this->papaya()->request->pageId,
      $this->papaya()->request->languageIdentifier
    );
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
    $parent->append($this->searchApi());
    $parent->append($filters);
  }

  /**
   * @param PapayaModuleBingApiSearch $searchApi
   * @return PapayaModuleBingApiSearch
   */
  public function searchApi(PapayaModuleBingApiSearch $searchApi = NULL) {
    if (NULL !== $searchApi) {
      $this->_searchApi = $searchApi;
    } elseif (NULL === $this->_searchApi) {
      $this->_searchApi = new PapayaModuleBingApiSearch(
        $this->content()->get('bing_api_endpoint', $this->_defaults['bing_api_endpoint']),
        $this->content()->get('bing_api_key', ''),
        $this->content()->get('bing_configuration_id', ''),
        $this->content()->get('bing_result_limit', $this->_defaults['bing_result_limit'])
      );
      $this->_searchApi->papaya($this->papaya());
    }
    return $this->_searchApi;
  }

  /**
   * Append the teaser output xml to the DOM.
   *
   * @see PapayaXmlAppendable::appendTo()
   * @param PapayaXmlElement $parent
   * @return NULL|PapayaXmlElement|void
   */
  public function appendQuoteTo(PapayaXmlElement $parent) {
    $parent->appendElement('title', [], $this->content()->get('title', ''));
    $parent->appendElement('text')->appendXml($this->content()->get('teaser', ''));
  }

  /**
   * The content is an {@see ArrayObject} containing the stored data.
   *
   * @see PapayaPluginEditable::content()
   * @param PapayaPluginEditableContent $content
   * @return PapayaPluginEditableContent
   */
  public function content(PapayaPluginEditableContent $content = NULL) {
    if (isset($content)) {
      $this->_content = $content;
    } elseif (NULL == $this->_content) {
      $this->_content = new PapayaPluginEditableContent();
      $this->_content->callbacks()->onCreateEditor = [$this, 'createEditor'];
    }
    return $this->_content;
  }

  /**
   * The configuration is an {@see ArrayObject} containing options that can affect the
   * execution of other methods (like appendTo()).
   *
   * @see PapayaPluginConfigurable::configuration()
   * @param PapayaObjectParameters $configuration
   * @return PapayaObjectParameters
   */
  public function configuration(PapayaObjectParameters $configuration = NULL) {
    if (isset($configuration)) {
      $this->_configuration = $configuration;
    } elseif (NULL == $this->_configuration) {
      $this->_configuration = new PapayaObjectParameters();
    }
    return $this->_configuration;
  }

  /**
   * The editor is used to change the stored data in the administration interface.
   *
   * In this case it the editor creates an dialog from a field definition.
   *
   * @see PapayaPluginEditableContent::editor()
   *
   * @param object $callbackContext
   * @param PapayaPluginEditableContent $content
   * @return PapayaPluginEditor
   * @throws \UnexpectedValueException
   */
  public function createEditor(
    /** @noinspection PhpUnusedParameterInspection */
    $callbackContext,
    PapayaPluginEditableContent $content
  ) {
    $editor = new PapayaAdministrationPluginEditorDialog($content);
    $dialog = $editor->dialog();
    $dialog->fields[] = new PapayaUiDialogFieldInput(
      new PapayaUiStringTranslated('Bing configuration id'),
      'bing_configuration_id'
    );
    $dialog->fields[] = new PapayaUiDialogFieldInput(
      new PapayaUiStringTranslated('Bing API Key'),
      'bing_api_key'
    );
    $dialog->fields[] = new PapayaUiDialogFieldInput(
      new PapayaUiStringTranslated('Bing API Endpoint'),
      'bing_api_endpoint',
      1024,
      $this->_defaults['bing_api_endpoint'],
      new PapayaFilterUrl()
    );
    $dialog->fields[] = new PapayaUiDialogFieldInputNumber(
      new PapayaUiStringTranslated('Items per page'),
      'bing_result_limit',
      $this->_defaults['bing_result_limit'],
      TRUE,
      2,
      3
    );
    $dialog->fields[] = $group = new PapayaUiDialogFieldGroup(
      new PapayaUiStringTranslated('Texts')
    );
    $group->fields[] = $field = new PapayaUiDialogFieldInput(
      new PapayaUiStringTranslated('Title'),
      'title',
      400
    );
    $field->setMandatory(TRUE);
    $group->fields[] = $field = new PapayaUiDialogFieldTextareaRichtext(
      new PapayaUiStringTranslated('Teaser'),
      'teaser',
      5,
      '',
      new PapayaFilterXml(),
      PapayaUiDialogFieldTextareaRichtext::RTE_SIMPLE
    );
    $group->fields[] = $field = new PapayaUiDialogFieldTextareaRichtext(
      new PapayaUiStringTranslated('Text'),
      'text',
      15,
      '',
      new PapayaFilterXml()
    );
    $editor->papaya($this->papaya());
    return $editor;
  }

  /**
   * Define the code definition parameters for the output.
   *
   * @see PapayaPluginCacheable::cacheable()
   * @param PapayaCacheIdentifierDefinition $definition
   * @return PapayaCacheIdentifierDefinition
   */
  public function cacheable(PapayaCacheIdentifierDefinition $definition = NULL) {
    if (isset($definition)) {
      $this->_cacheDefinition = $definition;
    } elseif (NULL == $this->_cacheDefinition) {
      $this->_cacheDefinition = new PapayaCacheIdentifierDefinitionGroup(
        new PapayaCacheIdentifierDefinitionPage(),
        new PapayaCacheIdentifierDefinitionParameters(
          ['q', 'q_page']
        )
      );
    }
    return $this->_cacheDefinition;
  }

  public function filters(PapayaPluginFilterContent $filters = NULL) {
    if (isset($filters)) {
      $this->_contentFilters = $filters;
    } elseif (NULL == $this->_contentFilters) {
      $this->_contentFilters = new PapayaPluginFilterContentRecords($this->_owner);
    }
    return $this->_contentFilters;
  }
}
