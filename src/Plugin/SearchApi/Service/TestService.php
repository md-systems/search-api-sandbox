<?php

namespace Drupal\search_api\Plugin\SearchApi\Service;

use Drupal\search_api\Index\IndexInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Service\ServicePluginBase;
use Drupal\search_api\Service\ServiceExtraInfoInterface;

/**
 * @SearchApiService(
 *   id = "search_api_test_service",
 *   label = @Translation("Test service"),
 *   description = @Translation("Dummy service implementation")
 * )
 */
class TestService extends ServicePluginBase implements ServiceExtraInfoInterface {

  /**
   * {@inheritdoc}
   */
  public function indexItems(IndexInterface $index, array $items) {
    return array_keys($items);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItems(IndexInterface $index, array $ids) {
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAllItems(IndexInterface $index = NULL) {}

  /**
   * {@inheritdoc}
   */
  public function search(QueryInterface $query) {
    if ($query->getKeys() && $query->getKeys()[0] == 'test') {
      return array(
        'result count' => 1,
        'results' => array(
          1 => array(
            'id' => 1,
            'score' => 1,
          ),
        ),
      );
    }
    else {
      return array(
        'result count' => 2,
        'results' => array(
          1 => array(
            'id' => 1,
            'score' => 1,
          ),
          2 => array(
            'id' => 2,
            'score' => 1,
          ),
        ),
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getExtraInformation() {
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

}
