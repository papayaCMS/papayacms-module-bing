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
    'CACHE_SERVICE' => 'file'
  );

  /**
   * @var \PapayaCacheService
   */
  private $_cache;

  public function createOptionsEditor(\PapayaPluginEditableOptions $options) {
    $editor = new \PapayaAdministrationPluginEditorDialog($options);
    $dialog = $editor->dialog();
    $dialog->fields[] = $group = new \PapayaUiDialogFieldGroup(
      'Bing API'
    );
    $group->fields[] = new \PapayaUiDialogFieldInput(
      new \PapayaUiStringTranslated('Subscription Key'),
      'BING_API_KEY'
    );
    $group->fields[] = new \PapayaUiDialogFieldInput(
      new \PapayaUiStringTranslated('Endpoint'),
      'BING_API_ENDPOINT',
      1024,
      self::$_defaults['BING_API_ENDPOINT'],
      new \PapayaFilterUrl()
    );
    $dialog->fields[] = $group = new \PapayaUiDialogFieldGroup(
      new \PapayaUiStringTranslated('Cache')
    );
    $group->fields[] = $field =new \PapayaUiDialogFieldSelect(
      new \PapayaUiStringTranslated('Service'),
      'CACHE_SERVICE',
      array('apc' => 'APC', 'file' => 'File system', 'memcache' => 'Memcache')
    );
    $field->setDefaultValue(self::$_defaults['CACHE_SERVICE']);
    $group->fields[] = $field =new \PapayaUiDialogFieldInput(
      new \PapayaUiStringTranslated('Path'),
      'CACHE_PATH',
      2000,
      $this->papaya()->options->get('PAPAYA_PATH_CACHE'),
      new \PapayaFilterFilePath()
    );
    $group->fields[] = $field = new \PapayaUiDialogFieldInput(
      new \PapayaUiStringTranslated('Memcache Servers'),
      'CACHE_MEMCACHE_SERVERS'
    );
    $editor->papaya($this->papaya());
    return $editor;
  }

  public function createSearchApi($configurationIdentifier, $resultLimit, $resultCacheTime = 0) {
    $searchApi = new Api\Search(
      $this->options()->get('BING_API_ENDPOINT', self::$_defaults['BING_API_ENDPOINT']),
      $this->options()->get('BING_API_KEY', ''),
      $configurationIdentifier,
      $resultLimit
    );
    $searchApi->cache($this->createCacheService(), $resultCacheTime);
    return $searchApi;
  }

  private function createCacheService($cache = NULL) {
    if (NULL !== $cache) {
      $this->_cache = $cache;
    } elseif (NULL === $this->_cache) {
      $configuration = new \PapayaCacheConfiguration();
      $configuration->assign(
        array(
          'SERVICE' => $this->options()->get('CACHE_SERVICE', self::$_defaults['CACHE_SERVICE']),
          'FILESYSTEM_PATH' => $this->options()->get(
            'CACHE_PATH', $this->papaya()->options->get('PAPAYA_PATH_CACHE')
          ),
          'MEMCACHE_SERVERS' => $this->options()->get('CACHE_MEMCACHE_SERVERS')
        )
      );
      $this->_cache = \PapayaCache::getService($configuration);
    }
    return $this->_cache;
  }
}
