<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\SearchApi\Processor\RenderedItem.
 */

namespace Drupal\search_api\Plugin\SearchApi\Processor;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Session\SearchApiUserSession;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @SearchApiProcessor(
 *   id = "rendered_item",
 *   label = @Translation("Rendered item"),
 *   description = @Translation("Adds an additional field containing the rendered item as it would look when viewed.")
 * );
 */
class RenderedItem extends ProcessorPluginBase {

  /**
   * The current_user service used by this plugin.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new "Rendered item" processor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   An object storing information about the current account.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(AccountProxyInterface $current_user, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\Core\Session\AccountProxyInterface $current_user */
    $current_user = $container->get('current_user');
    return new static($current_user, $configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'view_mode' => array(),
      'roles' => array(DRUPAL_ANONYMOUS_RID),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
      $view_modes = $datasource->getViewModes();
      if (count($view_modes) > 1) {
        $form['view_mode'][$datasource_id] = array(
          '#type' => 'select',
          '#title' => $this->t('View mode for data source @datasource', array('@datasource' => $datasource->label())),
          '#options' => $view_modes,
        );
        if (isset($this->configuration['view_mode'][$datasource_id])) {
          $form['view_mode'][$datasource_id]['#default_value'] = $this->configuration['view_mode'][$datasource_id];
        }
      }
      elseif ($view_modes) {
        $form['view_mode'][$datasource_id] = array(
          '#type' => 'value',
          '#value' => key($view_modes),
        );
      }
    }

    $form['roles'] = array(
      '#type' => 'select',
      '#title' => $this->t('User roles'),
      '#description' => $this->t('The data will be processed as seen by a user with the selected roles.'),
      '#options' => user_role_names(),
      '#multiple' => TRUE,
      '#default_value' => $this->configuration['roles'],
      '#required' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterPropertyDefinitions(array &$properties, DatasourceInterface $datasource = NULL) {
    if ($datasource) {
      return;
    }
    $definition = array(
      'type' => 'string',
      'label' => $this->t('Rendered HTML output'),
      'description' => $this->t('The complete HTML which would be created when viewing the item.'),
    );
    $properties['rendered_item'] = new DataDefinition($definition);
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array &$items) {
    // Annoyingly, this doc comment is needed for PHPStorm. See
    // http://youtrack.jetbrains.com/issue/WI-23586
    /** @var \Drupal\search_api\Item\ItemInterface[] $items */

    // To exploit any performance improvements that come with viewing multiple
    // objects at once, we first extract the passed objects and group them by
    // datasource, and only then view them.
    // We could do this for getting the original objects, but they should
    // already be set when indexing anyways.
    $item_objects = array();
    /** @var \Drupal\search_api\Item\FieldInterface[] $item_fields */
    $item_fields = array();
    foreach ($items as $item_id => $item) {
      if (!($field = $item->getField('rendered_item'))) {
        continue;
      }
      $item_fields[$item_id] = $field;
      $item_objects[$item->getDatasourceId()][$item_id] = $item->getOriginalObject();
    }

    // Were there any objects passed?
    if (!$item_objects) {
      return;
    }

    // Change the current user to our custom AccountInterface implementation
    // so we don't accidentally expose non-public information in this field.
    $original_user = $this->currentUser->getAccount();
    $this->currentUser->setAccount(new SearchApiUserSession($this->configuration['roles']));

    $build = array();
    foreach ($item_objects as $datasource_id => $objects) {
      if (!empty($this->configuration['view_mode'][$datasource_id])) {
        try {
          $build += $this->index->getDatasource($datasource_id)->viewMultipleItems($objects, $this->configuration['view_mode'][$datasource_id]);
        }
        catch (\InvalidArgumentException $e) {
          // Do nothing; we still need to reset the account and $build will be
          // empty anyways.
        }
      }
    }
    // Restore the user.
    $this->currentUser->setAccount($original_user);

    // Now add the rendered items to their respective fields.
    foreach ($build as $item_id => $render) {
      $item_fields[$item_id]->addValue(drupal_render($render));
    }
  }

}
