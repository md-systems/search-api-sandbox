<?php

/**
 * @file
 * Contains \Drupal\search_api\Tests\SearchApiViewsTest.
 */

namespace Drupal\search_api\Tests;

/**
 * Provides views tests for Search API.
 */
class SearchApiViewsTest extends SearchApiWebTestBase {

  /**
   * A Search API index ID.
   *
   * @var string
   */
  protected $indexId = 'database_search_index';

  use ExampleContentTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('search_api_test_views');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Search API Views test',
      'description' => 'Test views integration',
      'group' => 'Search API',
    );
  }

  public function setUp() {
    parent::setUp();
    $this->setUpExampleStructure();
  }

  /**
   * Tests a view with a fulltext search field.
   */
  public function testFulltextSearch() {
    $this->insertExampleContent();
    $this->indexItems($this->indexId);

    $this->drupalGet('search-api-test-fulltext');
    // By default, it should show all entities.
    foreach ($this->entities as $entity) {
      $this->assertText($entity->label());
    }

    // Search for something.
    $this->drupalGet('search-api-test-fulltext', array('query' => array('search_api_fulltext' => 'foobar')));

    // Now it should only find two entities.
    foreach ($this->entities as $id => $entity) {
      if ($id == 3) {
        $this->assertText($entity->label());
      }
      else {
        $this->assertNoText($entity->label());
      }
    }
  }

}
