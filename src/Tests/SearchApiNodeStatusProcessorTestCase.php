<?php

/**
 * @file
 * Contains \Drupal\search_api\Tests\SearchApiNodeStatusProcessorTestCase.
 */

namespace Drupal\search_api\Tests;

use Drupal\search_api\Plugin\SearchApi\Processor\NodeStatus;
use Drupal\system\Tests\Entity\EntityUnitTestBase;

/**
 * Tests the NodeStatus processor.
 */
class SearchApiNodeStatusProcessorTestCase extends SearchApiProcessorTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Tests NodeStatus Processor Plugin',
      'description' => 'Tests that the node processor plugin does as it should',
      'group' => 'Search API',
    );
  }

  /**
   * Creates a new processor object for use in the tests.
   */
  public function setUp() {
    parent::setUp('search_api_node_status_processor');

    // Create a node type for testing.
    $type = entity_create('node_type', array('type' => 'page', 'name' => 'page'));
    $type->save();
  }

  /**
   *  Test that items with status set to NULL are removed.
   */
  public function testNodeStatusWithNullStatus() {
    $items = $this->generateNodes(50, NULL);
    $this->processor->preprocessIndexItems($items);
    $this->assertTrue(count($items) == 0);
  }

  /**
   * Tests that items with status set to 0 are removed.
   */
  public function testNodeStatusWithZeroStatus() {
    $items = $this->generateNodes(50, 0);
    $this->processor->preprocessIndexItems($items);
    $this->assertEqual(count($items), 0);
  }

  /**
   * Tests that items with status set to FALSE are removed.
   */
  public function testNodeStatusWithFalseStatus() {
    $items = $this->generateNodes(50, FALSE);
    $this->processor->preprocessIndexItems($items);
    $this->assertEqual(count($items), 0);
  }

  /**
   * Tests that items with status set to 1 are not removed.
   */
  public function testNodeStatusWithOneStatus() {
    $items = $this->generateNodes(50, TRUE);
    $this->processor->preprocessIndexItems($items);
    $this->assertEqual(count($items), 50);
  }

  /**
   * Tests that items with status set to TRUE are not removed.
   */
  public function testNodeStatusWithTrueStatus() {
    $items = $this->generateNodes(50, TRUE);
    $this->processor->preprocessIndexItems($items);
    $this->assertEqual(count($items), 50);
  }

  /**
   * Tests that items with status set to mixed values
   */
  public function testNodeStatusWithMixedStatus() {
    $items = $this->generateNodes(50);
    $non_empty_count = 0;
    foreach ($items as $item) {
      $value = rand(0,1);
      $non_empty_count += $value;
      /** @var \Drupal\node\NodeInterface $node */
      $node = $item['#item'];
      $node->setPublished($value);
    }
    $this->processor->preprocessIndexItems($items);
    $this->assertEqual(count($items), $non_empty_count);
  }

  /**
   * Generates items for use with this test.
   *
   * @param int $number_of_items
   *   The number of items to create.
   * @param mixed $status
   *   The status to set for the nodes in the array.
   *
   * @return array
   *   An array of items to be indexed.
   */
  public function generateNodes($number_of_items, $status = NULL) {
    $item = entity_create('node', array('status' => $status, 'type' => 'page'));
    $generated_items = array();
    for ($i = 0; $i < $number_of_items; $i++) {
      $generated_items[] = array(
        'item' => clone $item,
        'datasource' => 'entity:node',
        'item_id' => rand(1, 100000),
      );
    }
    return parent::generateItems($generated_items);
  }

}
