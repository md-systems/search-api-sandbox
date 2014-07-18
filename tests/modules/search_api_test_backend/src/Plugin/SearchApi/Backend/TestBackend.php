<?php

/**
 * @file
 * Contains \Drupal\search_api_test_backend\Plugin\SearchApi\Backend\TestBackend.
 */

namespace Drupal\search_api_test_backend\Plugin\SearchApi\Backend;

use Drupal\search_api\Exception\SearchApiException;
use Drupal\search_api\Index\IndexInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Backend\BackendPluginBase;
use Drupal\search_api\Utility\Utility;

/**
 * @SearchApiBackend(
 *   id = "search_api_test_backend",
 *   label = @Translation("Test backend"),
 *   description = @Translation("Dummy backend implementation")
 * )
 */
class TestBackend extends BackendPluginBase {

  /**
   * {@inheritdoc}
   */
  public function supportsFeature($feature) {
    if ($feature == 'search_api_mlt') {
      return TRUE;
    }
    return parent::supportsFeature($feature);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $form['test'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Test'),
      '#default_value' => isset($this->configuration['test']) ? $this->configuration['test'] : '',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('test' => '');
  }

  /**
   * {@inheritdoc}
   */
  public function viewSettings() {
    return array(
      array(
        'label' => 'Dummy Info',
        'info' => 'Dummy Value',
        'status' => 'error',
      ),
      array(
        'label' => 'Dummy Info 2',
        'info' => 'Dummy Value 2',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function indexItems(IndexInterface $index, array $items) {
    $this->checkError(__FUNCTION__);
    return array_keys($items);
  }

  /**
   * {@inheritdoc}
   */
  public function addIndex(IndexInterface $index) {
    $this->checkError(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function updateIndex(IndexInterface $index) {
    $this->checkError(__FUNCTION__);
    $index->reindex();
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function removeIndex($index) {
    $this->checkError(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItems(IndexInterface $index, array $ids) {
    $this->checkError(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAllIndexItems(IndexInterface $index) {
    $this->checkError(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function search(QueryInterface $query) {
    $this->checkError(__FUNCTION__);

    $results = Utility::createSearchResultSet($query);
    $result_items = array();
    $datasources = $query->getIndex()->getDatasources();
    /** @var \Drupal\search_api\Datasource\DatasourceInterface $datasource */
    $datasource = reset($datasources);
    if ($query->getKeys() && $query->getKeys()[0] == 'test') {
      $item_id = Utility::createCombinedId($datasource->getPluginId(), '1');
      $item = Utility::createItem($query->getIndex(), $item_id, $datasource);
      $item->setScore(2);
      $item->setExcerpt('test');
      $result_items[$item_id] = $item;
    }
    elseif ($query->getOption('search_api_mlt')) {
      $item_id = Utility::createCombinedId($datasource->getPluginId(), '2');
      $item = Utility::createItem($query->getIndex(), $item_id, $datasource);
      $item->setScore(2);
      $item->setExcerpt('test test');
      $result_items[$item_id] = $item;
    }
    else {
      $item_id = Utility::createCombinedId($datasource->getPluginId(), '1');
      $item = Utility::createItem($query->getIndex(), $item_id, $datasource);
      $item->setScore(1);
      $result_items[$item_id] = $item;
      $item_id = Utility::createCombinedId($datasource->getPluginId(), '2');
      $item = Utility::createItem($query->getIndex(), $item_id, $datasource);
      $item->setScore(1);
      $result_items[$item_id] = $item;
    }
    $results->setResultCount(count($result_items));
    return $results;
  }

  /**
   * Throws an exception if set in the Drupal state for the given method.
   *
   * Also records (successful) calls to these methods.
   *
   * @param string $method
   *   The method on this object from which this method was called.
   *
   * @throws \Drupal\search_api\Exception\SearchApiException
   *   If state "search_api_test_backend.exception.$method" is TRUE.
   */
  protected function checkError($method) {
    $state = \Drupal::state();
    if ($state->get("search_api_test_backend.exception.$method")) {
      throw new SearchApiException($method);
    }
    $key = 'search_api_test_backend.methods_called.' . $this->server->id();
    $methods_called = $state->get($key, array());
    $methods_called[] = $method;
    $state->set($key, $methods_called);
  }

}
