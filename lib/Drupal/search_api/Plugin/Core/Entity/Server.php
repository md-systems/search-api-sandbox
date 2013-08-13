<?php

/**
 * @file
 * Contains Drupal\search_api\Plugin\Core\Entity\Server.
 */

namespace Drupal\search_api\Plugin\Core\Entity;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\search_api\Plugin\search_api\QueryInterface;
use Drupal\search_api\Plugin\search_api\ServiceInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\ServerInterface;

/**
 * Class representing a search server.
 *
 * @EntityType(
 *   id = "search_api_server",
 *   label = @Translation("Search server"),
 *   controllers = {
 *     "storage" = "Drupal\search_api\ServerStorageController",
 *     "access" = "Drupal\search_api\ServerAccessController",
 *     "form" = {
 *       "default" = "Drupal\search_api\ServerFormController",
 *       "delete" = "Drupal\search_api\Form\IndexDeleteForm"
 *     }
 *   },
 *   config_prefix = "search_api.server",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class Server extends ConfigEntityBase implements ServerInterface {

  /* Database values that will be set when object is loaded: */

  /**
   * The primary identifier for a server.
   *
   * @var integer
   */
  public $id = 0;

  /**
   * The displayed name for a server.
   *
   * @var string
   */
  public $name = '';

  /**
   * The server UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The displayed description for a server.
   *
   * @var string
   */
  public $description = '';

  /**
   * The id of the service class to use for this server.
   *
   * @var string
   */
  public $class = '';

  /**
   * The options used to configure the service object.
   *
   * @var array
   */
  public $options = array();

  /**
   * A flag indicating whether the server is enabled.
   *
   * @var integer
   */
  public $enabled = 1;

  /**
   * Proxy object for invoking service methods.
   *
   * @var \Drupal\search_api\Plugin\search_api\ServiceInterface
   */
  protected $proxy;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function uri() {
    return array(
     'path' => 'admin/config/search/search_api/server/' . $this->id(),
      'options' => array(
        'entity_type' => $this->entityType,
        'entity' => $this,
      ),
    );
  }

  /**
   * Helper method for updating entity properties.
   *
   * NOTE: You shouldn't change any properties of this object before calling
   * this method, as this might lead to the fields not being saved correctly.
   *
   * @param array $fields
   *   The new field values.
   *
   * @return
   *   SAVE_UPDATED on success, FALSE on failure, 0 if the fields already had
   *   the specified values.
   */
  public function update(array $fields) {
    $changeable = array('name' => 1, 'enabled' => 1, 'description' => 1, 'options' => 1);
    $changed = FALSE;
    foreach ($fields as $field => $value) {
      if (isset($changeable[$field]) && $value !== $this->$field) {
        $this->$field = $value;
        $changed = TRUE;
      }
    }
    // If there are no new values, just return 0.
    if (!$changed) {
      return 0;
    }
    return $this->save();
  }

  /**
   * Magic method for determining which fields should be serialized.
   *
   * Serialize all properties except the proxy object.
   *
   * @return array
   *   An array of properties to be serialized.
   */
  public function __sleep() {
    $ret = get_object_vars($this);
    unset($ret['proxy'], $ret['status'], $ret['module'], $ret['is_new']);
    return array_keys($ret);
  }

  /**
   * Helper method for ensuring the proxy object is set up.
   */
  protected function ensureProxy() {
    if (!isset($this->proxy)) {
      $class = search_api_get_service_info($this->class);
      if ($class && class_exists($class['class'])) {
        if (empty($this->options)) {
          // We always have to provide the options.
          $this->options = array();
        }
        $this->proxy = new $class['class']($this);
      }
      if (!($this->proxy instanceof ServiceInterface)) {
        throw new SearchApiException(t('Search server with machine name @name specifies illegal service class @class.', array('@name' => $this->machine_name, '@class' => $this->class)));
      }
    }
  }

  /**
   * If the service class defines additional methods, not specified in the
   * ServiceInterface interface, then they are called via this magic
   * method.
   */
  public function __call($name, $arguments = array()) {
    $this->ensureProxy();
    return call_user_func_array(array($this->proxy, $name), $arguments);
  }

  // Proxy methods

  // For increased clarity, and since some parameters are passed by reference,
  // we don't use the __call() magic method for those.

  public function configurationForm(array $form, array &$form_state) {
    $this->ensureProxy();
    return $this->proxy->configurationForm($form, $form_state);
  }

  public function configurationFormValidate(array $form, array &$values, array &$form_state) {
    $this->ensureProxy();
    return $this->proxy->configurationFormValidate($form, $values, $form_state);
  }

  public function configurationFormSubmit(array $form, array &$values, array &$form_state) {
    $this->ensureProxy();
    return $this->proxy->configurationFormSubmit($form, $values, $form_state);
  }

  public function supportsFeature($feature) {
    $this->ensureProxy();
    return $this->proxy->supportsFeature($feature);
  }

  public function viewSettings() {
    $this->ensureProxy();
    return $this->proxy->viewSettings();
  }

  public function postCreate(EntityStorageControllerInterface $storage_controller) {
    $this->ensureProxy();
    return $this->proxy->postCreate();
  }

  public function postUpdate() {
    $this->ensureProxy();
    return $this->proxy->postUpdate();
  }

  public function preDelete() {
    $this->ensureProxy();
    return $this->proxy->preDelete();
  }

  public function addIndex(Index $index) {
    $this->ensureProxy();
    return $this->proxy->addIndex($index);
  }

  public function fieldsUpdated(Index $index) {
    $this->ensureProxy();
    return $this->proxy->fieldsUpdated($index);
  }

  public function removeIndex($index) {
    $this->ensureProxy();
    return $this->proxy->removeIndex($index);
  }

  public function indexItems(Index $index, array $items) {
    $this->ensureProxy();
    return $this->proxy->indexItems($index, $items);
  }

  public function deleteItems($ids = 'all', Index $index = NULL) {
    $this->ensureProxy();
    return $this->proxy->deleteItems($ids, $index);
  }

  public function search(QueryInterface $query) {
    $this->ensureProxy();
    return $this->proxy->search($query);
  }

}
