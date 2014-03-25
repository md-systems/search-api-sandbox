<?php

/**
 * @file
 * Contains Drupal\search_api\Plugin\IndexPluginBase.
 */

namespace Drupal\search_api\Plugin;

use Drupal\search_api\Index\IndexInterface;

/**
 * Base class for plugins that are associated with a certain index.
 */
abstract class IndexPluginBase extends ConfigurablePluginBase implements IndexPluginInterface {

  /**
   * The index this processor is configured for.
   *
   * @var \Drupal\search_api\Index\IndexInterface
   */
  protected $index;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (!empty($configuration['index']) && $configuration['index'] instanceof IndexInterface) {
      $this->setIndex($configuration['index']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIndex() {
    return $this->index;
  }

  /**
   * {@inheritdoc}
   */
  public function setIndex(IndexInterface $index) {
    $this->index = $index;
  }

}
