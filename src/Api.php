<?php

namespace Papaya\Module\Bing;

class Api
  extends
    \PapayaObjectInteractive
  implements
    \PapayaPluginAdaptable {

  use
    \PapayaPluginEditableOptionsAggregation;

  private static $_defaults = array(
    'BING_API_ENDPOINT' => 'https://api.cognitive.microsoft.com/bingcustomsearch/v7.0/search',
  );

  public function createOptionsEditor(\PapayaPluginEditableOptions $options) {
    $editor = new \PapayaAdministrationPluginEditorDialog($options);
    $dialog = $editor->dialog();
    $dialog->fields[] = new \PapayaUiDialogFieldInput(
      new \PapayaUiStringTranslated('Bing API Subscription Key'),
      'BING_API_KEY'
    );
    $dialog->fields[] = new \PapayaUiDialogFieldInput(
      new \PapayaUiStringTranslated('Bing API Endpoint'),
      'BING_API_ENDPOINT',
      1024,
      self::$_defaults['BING_API_ENDPOINT'],
      new \PapayaFilterUrl()
    );
    $editor->papaya($this->papaya());
    return $editor;
  }

  public function createSearchApi($configurationIdentifier, $resultLimit) {
    return new Api\Search(
      $this->options()->get('BING_API_ENDPOINT', self::$_defaults['BING_API_ENDPOINT']),
      $this->options()->get('BING_API_KEY', ''),
      $configurationIdentifier,
      $resultLimit
    );
  }
}
