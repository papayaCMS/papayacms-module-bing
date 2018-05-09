<?php

namespace Papaya\Module\Bing;

use PapayaPluginEditableOptions;

class Page
  extends
    \PapayaObjectInteractive
  implements
    \PapayaPluginConfigurable,
    \PapayaPluginAppendable,
    \PapayaPluginQuoteable,
    \PapayaPluginEditable,
    \PapayaPluginAdaptable,
    \PapayaPluginCacheable {

  use
    \PapayaPluginConfigurableAggregation,
    \PapayaPluginEditableContentAggregation,
    \PapayaPluginEditableOptionsAggregation,
    \PapayaPluginCacheableAggregation,
    \PapayaPluginFilterAggregation;

  /**
   * @var \Papaya\Module\Bing\Api\Search
   */
  private $_searchApi;

  private $_defaults = [
    'BING_API_ENDPOINT' => 'https://api.cognitive.microsoft.com/bingcustomsearch/v7.0/search',
    'bing_result_limit' => 10,
    'search_term_parameter' => 'q'
  ];

  public function __construct($page) {
    $this->_page = $page;
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
    $parent->append($this->searchApi());
    $parent->append($filters);
  }

  /**
   * @param \Papaya\Module\Bing\Api\Search $searchApi
   * @return \Papaya\Module\Bing\Api\Search
   */
  public function searchApi(Api\Search $searchApi = NULL) {
    if (NULL !== $searchApi) {
      $this->_searchApi = $searchApi;
    } elseif (NULL === $this->_searchApi) {
      $this->_searchApi = new Api\Search(
        $this->content()->get('search_term_parameter', $this->_defaults['search_term_parameter']),
        $this->options()->get('BING_API_ENDPOINT', $this->_defaults['BING_API_ENDPOINT']),
        $this->options()->get('BING_API_KEY', ''),
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
   * @see \PapayaXmlAppendable::appendTo()
   * @param \PapayaXmlElement $parent
   * @return NULL|\PapayaXmlElement|void
   */
  public function appendQuoteTo(\PapayaXmlElement $parent) {
    $parent->appendElement('title', [], $this->content()->get('title', ''));
    $parent->appendElement('text')->appendXml($this->content()->get('teaser', ''));
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

  public function createOptionsEditor(PapayaPluginEditableOptions $content) {
    $editor = new \PapayaAdministrationPluginEditorDialog($content);
    $dialog = $editor->dialog();
    $dialog->fields[] = new \PapayaUiDialogFieldInput(
      new \PapayaUiStringTranslated('Bing API Key'),
      'BING_API_KEY'
    );
    $dialog->fields[] = new \PapayaUiDialogFieldInput(
      new \PapayaUiStringTranslated('Bing API Endpoint'),
      'BING_API_ENDPOINT',
      1024,
      $this->_defaults['BING_API_ENDPOINT'],
      new \PapayaFilterUrl()
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
