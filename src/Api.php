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
    'CACHE_SERVICE' => 'file',
    'DISABLE_SSL_PEER_VERIFICATION' => FALSE
  );

  /**
   * @var \PapayaCacheService
   */
  private $_cache;

  public function createOptionsEditor(\PapayaPluginEditableOptions $options) {
    $configuration = new \PapayaAdministrationPluginEditorDialog($options);
    $configuration->papaya($this->papaya());
    $dialog = $configuration->dialog();
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
    $group->fields[] = new \PapayaUiDialogFieldInputCheckbox(
      new \PapayaUiStringTranslated('Disable SSL Peer Verification'),
      'DISABLE_SSL_PEER_VERIFICATION',
      self::$_defaults['DISABLE_SSL_PEER_VERIFICATION'],
      FALSE
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

    $cacheManagement = new \PapayaAdministrationPluginEditorDialog($options);
    $cacheManagement->onExecute(
      function() {
        if ($cache = $this->createCacheService()) {
          $deleted = $cache->delete('BING_CUSTOM_SEARCH');
          if (TRUE === $deleted) {
            $this->papaya()->messages->display(
              \PapayaMessage::SEVERITY_INFO,
              new \PapayaUiStringTranslated('Cache deleted.')
            );
          } elseif ($deleted > 0) {
            $this->papaya()->messages->display(
              \PapayaMessage::SEVERITY_INFO,
              new \PapayaUiStringTranslated('%d cache item(s) deleted.', array($deleted))
            );
          } elseif ($deleted === 0) {
            $this->papaya()->messages->display(
              \PapayaMessage::SEVERITY_INFO,
              new \PapayaUiStringTranslated('Cache is empty.')
            );
          } else {
            $this->papaya()->messages->display(
              \PapayaMessage::SEVERITY_ERROR,
              new \PapayaUiStringTranslated('Cache delete failed.')
            );
          }
        } else {
          $this->papaya()->messages->display(
            \PapayaMessage::SEVERITY_WARNING,
            new \PapayaUiStringTranslated('No cache configured.')
          );
        }
      }
    );
    $dialog = $cacheManagement->dialog();
    $dialog->caption = new \PapayaUiStringTranslated('Confirmation');
    $dialog->fields[] = new \PapayaUiDialogFieldInformation(
      new \PapayaUiStringTranslated(
        'Delete Cache?'
      ),
      'places-trash'
    );
    $dialog->options->topButtons = FALSE;
    $dialog->buttons[0] = new \PapayaUiDialogButtonSubmit(
      new \PapayaUiStringTranslated('Delete')
    );

    $editor = new \PapayaAdministrationPluginEditorGroup($options);
    $editor->add(
      $configuration,
      new \PapayaUiStringTranslated('Settings'),
      'items-option'
    );
    $editor->add(
      $cacheManagement,
      new \PapayaUiStringTranslated('Manage Cache'),
      'actions-database-refresh'
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
    if ($this->options()->get('DISABLE_SSL_PEER_VERIFICATION', self::$_defaults['DISABLE_SSL_PEER_VERIFICATION'])) {
      $searchApi->disableSSLPeerVerification();
    }
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
