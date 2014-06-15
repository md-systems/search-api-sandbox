<?php

/**
 * @file
 * Contains \Drupal\search_api\Tests\Plugin\Processor\RoleFilterTest.
 */

namespace Drupal\search_api\Tests\Plugin\Processor;

use Drupal\search_api\Index\IndexInterface;
use Drupal\search_api\Plugin\SearchApi\Processor\RoleFilter;
use Drupal\search_api\Utility\Utility;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Role Filter processor plugin.
 *
 * @group Drupal
 * @group search_api
 *
 * @see \Drupal\search_api\Plugin\SearchApi\Processor\RoleFilter
 */
class RoleFilterTest extends UnitTestCase {

  /**
   * Stores the processor to be tested.
   *
   * @var \Drupal\search_api\Plugin\SearchApi\Processor\RoleFilter
   */
  protected $processor;

  /**
   * The test items to use.
   *
   * @var \Drupal\search_api\Item\ItemInterface[]
   */
  protected $items = array();

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'RoleFilter Processor Plugin',
      'description' => 'Unit test of preprocessor for role filter.',
      'group' => 'Search API',
    );
  }

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp() {
    parent::setUp();

    $this->processor = new RoleFilter(array(), 'role_filter', array());

    /** @var \Drupal\search_api\Index\IndexInterface $index */
    $index = $this->getMock('Drupal\search_api\Index\IndexInterface');

    $node_datasource = $this->getMock('Drupal\search_api\Datasource\DatasourceInterface');
    $node_datasource->expects($this->any())
      ->method('getEntityTypeId')
      ->will($this->returnValue('node'));
    /** @var \Drupal\search_api\Datasource\DatasourceInterface $node_datasource */
    $user_datasource = $this->getMock('Drupal\search_api\Datasource\DatasourceInterface');
    $user_datasource->expects($this->any())
      ->method('getEntityTypeId')
      ->will($this->returnValue('user'));
    /** @var \Drupal\search_api\Datasource\DatasourceInterface $user_datasource */

    $item = Utility::createItem($index, 'entity:node' . IndexInterface::DATASOURCE_ID_SEPARATOR . '1:en', $node_datasource);
    $node = $this->getMockBuilder('Drupal\search_api\Tests\TestNodeInterface')
      ->disableOriginalConstructor()
      ->getMock();
    /** @var \Drupal\node\NodeInterface $node */
    $item->setOriginalObject($node);
    $this->items[$item->getId()] = $item;

    $item = Utility::createItem($index, 'entity:user' . IndexInterface::DATASOURCE_ID_SEPARATOR . '1:en', $user_datasource);
    $account1 = $this->getMockBuilder('Drupal\search_api\Tests\TestUserInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $account1->expects($this->any())
      ->method('getRoles')
      ->will($this->returnValue(array('authenticated' => 'authenticated', 'editor' => 'editor')));
    /** @var \Drupal\user\UserInterface $account1 */
    $item->setOriginalObject($account1);
    $this->items[$item->getId()] = $item;

    $item = Utility::createItem($index, 'entity:user' . IndexInterface::DATASOURCE_ID_SEPARATOR . '2:en', $user_datasource);
    $account2 = $this->getMockBuilder('Drupal\search_api\Tests\TestUserInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $account2->expects($this->any())
      ->method('getRoles')
      ->will($this->returnValue(array('authenticated' => 'authenticated')));
    /** @var \Drupal\user\UserInterface $account2 */
    $item->setOriginalObject($account2);
    $this->items[$item->getId()] = $item;
  }

  /**
   * Tests preprocessing search items with an inclusive filter.
   */
  public function testFilterInclusive() {
    $configuration['roles'] = array('authenticated' => 'authenticated');
    $configuration['default'] = 0;
    $this->processor->setConfiguration($configuration);

    $this->processor->preprocessIndexItems($this->items);

    $this->assertTrue(!empty($this->items['entity:user' . IndexInterface::DATASOURCE_ID_SEPARATOR . '1:en']), 'User with two roles was not removed.');
    $this->assertTrue(!empty($this->items['entity:user' . IndexInterface::DATASOURCE_ID_SEPARATOR . '2:en']), 'User with only the authenticated role was not removed.');
    $this->assertTrue(!empty($this->items['entity:node' . IndexInterface::DATASOURCE_ID_SEPARATOR . '1:en']), 'Node item was not removed.');
  }

  /**
   * Tests preprocessing search items with an exclusive filter.
   */
  public function testFilterExclusive() {
    $configuration['roles'] = array('editor' => 'editor');
    $configuration['default'] = 1;
    $this->processor->setConfiguration($configuration);

    $this->processor->preprocessIndexItems($this->items);

    $this->assertTrue(empty($this->items['entity:user' . IndexInterface::DATASOURCE_ID_SEPARATOR . '1:en']), 'User with editor role was successfully removed.');
    $this->assertTrue(!empty($this->items['entity:user' . IndexInterface::DATASOURCE_ID_SEPARATOR . '2:en']), 'User without the editor role was not removed.');
    $this->assertTrue(!empty($this->items['entity:node' . IndexInterface::DATASOURCE_ID_SEPARATOR . '1:en']), 'Node item was not removed.');
  }

}
