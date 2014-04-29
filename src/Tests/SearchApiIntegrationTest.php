<?php

/**
 * @file
 * Contains \Drupal\search_api\Tests\SearchApiIntegrationTest.
 */

namespace Drupal\search_api\Tests;

/**
 * Provides integration tests for Search API.
 */
class SearchApiIntegrationTest extends SearchApiWebTestBase {

  /**
   * A Search API server ID.
   *
   * @var string
   */
  protected $serverId;

  /**
   * A Search API index ID.
   *
   * @var string
   */
  protected $indexId;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Search API integration test',
      'description' => 'Test creation of Search API indexes and servers through the UI.',
      'group' => 'Search API',
    );
  }

  /**
   * Tests various UI interactions between servers and indexes.
   */
  public function testFramework() {
    $this->drupalLogin($this->adminUser);

    // Test that the overview page exists and its permissions work.
    $this->drupalGet('admin/config');
    $this->assertText('Search API', 'Search API menu link is displayed.');

    $this->drupalGet('admin/config/search/search-api');
    $this->assertResponse(200, 'Admin user can access the overview page.');

    $this->drupalLogin($this->unauthorizedUser);
    $this->drupalGet('admin/config/search/search-api');
    $this->assertResponse(403, "User without permissions doesn't have access to the overview page.");

    // Login as an admin user for the rest of the tests.
    $this->drupalLogin($this->adminUser);

    $this->createServer();
    $this->createIndex();
    $this->trackContent();

    $this->addFieldsToIndex();
    $this->addAdditionalFieldsToIndex();

    $this->setReadOnly();
    $this->disableEnableIndex();
    $this->changeIndexDatasource();
    $this->changeIndexServer();

  }

  protected function createServer() {
    $settings_path = $this->urlGenerator->generateFromRoute('search_api.server_add', array(), array('absolute' => TRUE));

    $this->drupalGet($settings_path);
    $this->assertResponse(200, 'Server add page exists');

    $edit = array(
      'name' => '',
      'status' => 1,
      'description' => 'A server used for testing.',
      'servicePluginId' => '',
    );

    $this->drupalPostForm($settings_path, $edit, t('Save'));
    $this->assertText(t('!name field is required.', array('!name' => t('Server name'))));
    $this->assertText(t('!name field is required.', array('!name' => t('Service'))));

    $edit = array(
      'name' => 'Search API test server',
      'status' => 1,
      'description' => 'A server used for testing.',
      'servicePluginId' => 'search_api_test_service',
    );
    $this->drupalPostForm($settings_path, $edit, t('Save'));
    $this->assertText(t('!name field is required.', array('!name' => t('Machine-readable name'))));

    $this->serverId = 'test_server';

    $edit = array(
      'name' => 'Search API test server',
      'machine_name' => $this->serverId,
      'status' => 1,
      'description' => 'A server used for testing.',
      'servicePluginId' => 'search_api_test_service',
    );

    $this->drupalPostForm(NULL, $edit, t('Save'));

    $this->assertText(t('The server was successfully saved.'));
    $this->assertUrl('admin/config/search/search-api/server/' . $this->serverId, array(), t('Correct redirect to server page.'));
  }

  protected function createIndex() {
    $settings_path = $this->urlGenerator->generateFromRoute('search_api.index_add', array(), array('absolute' => TRUE));

    $this->drupalGet($settings_path);
    $this->assertResponse(200);
    $edit = array(
      'status' => 1,
      'description' => 'An index used for testing.',
    );

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('!name field is required.', array('!name' => t('Index name'))));
    $this->assertText(t('!name field is required.', array('!name' => t('Machine-readable name'))));
    $this->assertText(t('!name field is required.', array('!name' => t('Data types'))));

    $this->indexId = 'test_index';

    $edit = array(
      'name' => 'Search API test index',
      'machine_name' => $this->indexId,
      'status' => 1,
      'description' => 'An index used for testing.',
      'serverMachineName' => $this->serverId,
      'datasourcePluginIds[]' => array('entity:node'),
    );

    $this->drupalPostForm(NULL, $edit, t('Save'));

    $this->assertText(t('The index was successfully saved.'));
    $this->assertUrl('admin/config/search/search-api/index/' . $this->indexId, array(), t('Correct redirect to index page.'));

    /** @var $index \Drupal\search_api\Entity\Index */
    $index = entity_load('search_api_index', $this->indexId, TRUE);

    $this->assertEqual($index->name, $edit['name'], t('Name correctly inserted.'));
    $this->assertEqual($index->machine_name, $edit['machine_name'], t('Index machine name correctly inserted.'));
    $this->assertTrue($index->status, t('Index status correctly inserted.'));
    $this->assertEqual($index->description, $edit['description'], t('Index machine name correctly inserted.'));
    $this->assertEqual($index->serverMachineName, $edit['serverMachineName'], t('Index server machine name correctly inserted.'));
    $this->assertEqual($index->datasourcePluginIds, $edit['datasourcePluginIds[]'], t('Index datasource id correctly inserted.'));
  }

  protected function addFieldsToIndex() {
    $settings_path = 'admin/config/search/search-api/index/' . $this->indexId . '/fields/entity:node';

    $this->drupalGet($settings_path);
    $this->assertResponse(200);

    $edit = array(
      'fields[entity:node|nid][indexed]' => 1,
      'fields[entity:node|title][indexed]' => 1,
      'fields[entity:node|title][type]' => 'text',
      'fields[entity:node|title][boost]' => '21.0',
      'fields[entity:node|body][indexed]' => 1,
    );

    $this->drupalPostForm($settings_path, $edit, t('Save changes'));
    $this->assertText(t('The indexed fields were successfully changed. The index was cleared and will have to be re-indexed with the new settings.'));

    /** @var $index \Drupal\search_api\Entity\Index */
    $index = entity_load('search_api_index', $this->indexId, TRUE);
    $fields = $index->getFields();

    $this->assertEqual($fields['entity:node|nid']['indexed'], $edit['fields[entity:node|nid][indexed]'], t('nid field is indexed.'));
    $this->assertEqual($fields['entity:node|title']['indexed'], $edit['fields[entity:node|title][indexed]'], t('title field is indexed.'));
    $this->assertEqual($fields['entity:node|title']['type'], $edit['fields[entity:node|title][type]'], t('title field type is text.'));
    $this->assertEqual($fields['entity:node|title']['boost'], $edit['fields[entity:node|title][boost]'], t('title field boost value is 21.'));

    // Check that a 'parent_data_type.data_type' Search API field type => data
    // type mapping relationship works.
    $this->assertEqual($fields['entity:node|body']['type'], 'text', 'Complex field mapping relationship works.');
  }

  protected function addAdditionalFieldsToIndex() {
    // Test that an entity reference field which targets a content entity is
    // shown.
    $this->assertFieldByName('additional[field][entity:node|uid]', NULL, 'Additional entity reference field targeting a content entity type is displayed.');

    // Test that an entity reference field which targets a config entity is not
    // shown as an additional field option.
    $this->assertNoFieldByName('additional[field][entity:node|type]', NULL,'Additional entity reference field targeting a config entity type is not displayed.');

    // @todo Implement more tests for additional fields.
  }

  protected function trackContent() {
    // Initially there should be no tracked items, because there are no nodes
    $tracked_items = $this->countTrackedItems();

    $this->assertEqual($tracked_items, 0, t('No items are tracked yet'));

    // Add two articles and a page
    $article1 = $this->drupalCreateNode(array('type' => 'article'));
    $article2 = $this->drupalCreateNode(array('type' => 'article'));
    $page1 = $this->drupalCreateNode(array('type' => 'page'));

    // Those 3 new nodes should be added to the index immediately
    $tracked_items = $this->countTrackedItems();
    $this->assertEqual($tracked_items, 3, t('Three items are tracked'));

    // Create the edit index path
    $settings_path = 'admin/config/search/search-api/index/' . $this->indexId . '/edit';

    // Test disabling the index
    $this->drupalGet($settings_path);
    $edit = array(
      'status' => FALSE,
      'datasourcePluginConfigs[entity:node][default]' => 0,
      'datasourcePluginConfigs[entity:node][bundles][article]' => FALSE,
      'datasourcePluginConfigs[entity:node][bundles][page]' => FALSE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $tracked_items = $this->countTrackedItems();
    $this->assertEqual($tracked_items, 0, t('No items are tracked'));

    // Test enabling the index
    $this->drupalGet($settings_path);

    $edit = array(
      'status' => TRUE,
      'datasourcePluginConfigs[entity:node][default]' => 0,
      'datasourcePluginConfigs[entity:node][bundles][article]' => TRUE,
      'datasourcePluginConfigs[entity:node][bundles][page]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $tracked_items = $this->countTrackedItems();
    $this->assertEqual($tracked_items, 3, t('Three items are tracked'));

    // Test putting default to zero and no bundles checked.
    // This will ignore all bundles except the ones that are checked.
    // This means all the items should get deleted.
    $this->drupalGet($settings_path);
    $edit = array(
      'status' => FALSE,
      'datasourcePluginConfigs[entity:node][default]' => 0,
      'datasourcePluginConfigs[entity:node][bundles][article]' => FALSE,
      'datasourcePluginConfigs[entity:node][bundles][page]' => FALSE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $tracked_items = $this->countTrackedItems();
    $this->assertEqual($tracked_items, 0, t('No items are tracked'));

    // Test putting default to zero and the article bundle checked.
    // This will ignore all bundles except the ones that are checked.
    // This means all articles should be added.
    $this->drupalGet($settings_path);

    $edit = array(
      'status' => TRUE,
      'datasourcePluginConfigs[entity:node][default]' => 0,
      'datasourcePluginConfigs[entity:node][bundles][article]' => TRUE,
      'datasourcePluginConfigs[entity:node][bundles][page]' => FALSE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $this->drupalGet($settings_path);

    $tracked_items = $this->countTrackedItems();
    $this->assertEqual($tracked_items, 2, t('Two items are tracked'));

    // Test putting default to zero and the page bundle checked.
    // This will ignore all bundles except the ones that are checked.
    // This means all pages should be added.
    $this->drupalGet($settings_path);
    $edit = array(
      'status' => TRUE,
      'datasourcePluginConfigs[entity:node][default]' => 0,
      'datasourcePluginConfigs[entity:node][bundles][article]' => FALSE,
      'datasourcePluginConfigs[entity:node][bundles][page]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $tracked_items = $this->countTrackedItems();
    $this->assertEqual($tracked_items, 1, t('One items is tracked'));

    // Test putting default to one and the article bundle checked.
    // This will add all bundles except the ones that are checked.
    // This means all pages should be added.
    $this->drupalGet($settings_path);
    $edit = array(
      'status' => TRUE,
      'datasourcePluginConfigs[entity:node][default]' => 1,
      'datasourcePluginConfigs[entity:node][bundles][article]' => TRUE,
      'datasourcePluginConfigs[entity:node][bundles][page]' => FALSE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $tracked_items = $this->countTrackedItems();
    $this->assertEqual($tracked_items, 1, t('One item is tracked'));

    // Test putting default to one and the page bundle checked.
    // This will ignore all bundles except the ones that are checked.
    // This means all articles should be added.
    $this->drupalGet($settings_path);
    $edit = array(
      'status' => TRUE,
      'datasourcePluginConfigs[entity:node][default]' => 1,
      'datasourcePluginConfigs[entity:node][bundles][article]' => FALSE,
      'datasourcePluginConfigs[entity:node][bundles][page]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $tracked_items = $this->countTrackedItems();
    $this->assertEqual($tracked_items, 2, t('Two items are tracked'));

    // Now lets delete an article. That should remove one item from the item
    // table
    $article1->delete();

    $tracked_items = $this->countTrackedItems();
    $this->assertEqual($tracked_items, 1, t('One item is tracked'));


  }

  /**
   * Counts the number of tracked items from an index.
   *
   * @return int
   */
  protected function countTrackedItems() {
    /** @var $index \Drupal\search_api\Entity\Index */
    $index = entity_load('search_api_index', $this->indexId);
    return $index->getTracker()->getTotalItemsCount();
  }

  /**
   * Sets an index to read only and checks if it reacts accordingly.
   *
   * The expected behavior is such that when an index is set to Read Only it
   * keeps tracking but when it comes to indexing it does not proceed to send
   * items to the server.
   */
  protected function setReadOnly() {
    /** @var $index \Drupal\search_api\Entity\Index */
    $index = entity_load('search_api_index', $this->indexId, TRUE);
    $index->reindex();

    // Go to the index edit path
    $settings_path = 'admin/config/search/search-api/index/' . $this->indexId . '/edit';
    $this->drupalGet($settings_path);
    $edit = array(
      'status' => TRUE,
      'readOnly' => TRUE,
      'datasourcePluginConfigs[entity:node][default]' => 0,
      'datasourcePluginConfigs[entity:node][bundles][article]' => TRUE,
      'datasourcePluginConfigs[entity:node][bundles][page]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // This should have 2 items in the index

    $index = entity_load('search_api_index', $this->indexId, TRUE);
    $remaining_before = $index->getTracker()->getRemainingItemsCount();
    $index->index();
    $remaining_after = $index->getTracker()->getRemainingItemsCount();
    $this->assertEqual($remaining_before, $remaining_after, t('No items were indexed after setting to readOnly'));

    $settings_path = 'admin/config/search/search-api/index/' . $this->indexId . '/edit';
    $this->drupalGet($settings_path);

    $edit = array(
      'status' => TRUE,
      'readOnly' => FALSE,
      'datasourcePluginConfigs[entity:node][default]' => 0,
      'datasourcePluginConfigs[entity:node][bundles][article]' => TRUE,
      'datasourcePluginConfigs[entity:node][bundles][page]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $index = entity_load('search_api_index', $this->indexId, TRUE);
    $remaining_before = $index->getTracker()->getRemainingItemsCount();
    $index->index();
    $remaining_after = $index->getTracker()->getRemainingItemsCount();
    $this->assertNotEqual($remaining_before, $remaining_after, t('Items were indexed after removing the readOnly flag'));

  }

  /**
   * Disables and enables an index and checks if it reacts accordingly.
   *
   * The expected behavior is such that when an index is disabled, all the items
   * from this index in the tracker are removed and it also tells the service
   * to remove all items from this index.
   *
   * When it is enabled again, the items are re-added to the tracker.
   *
   */
  protected function disableEnableIndex() {
    /** @var $index \Drupal\search_api\Entity\Index */
    $index = entity_load('search_api_index', $this->indexId, TRUE);
    $index->reindex();

    // Go to the index edit path
    $settings_path = 'admin/config/search/search-api/index/' . $this->indexId . '/edit';
    $this->drupalGet($settings_path);
    // Disable the index
    $edit = array(
      'status' => FALSE,
      'datasourcePluginConfigs[entity:node][default]' => 0,
      'datasourcePluginConfigs[entity:node][bundles][article]' => TRUE,
      'datasourcePluginConfigs[entity:node][bundles][page]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $index = entity_load('search_api_index', $this->indexId, TRUE);
    $tracked_items = $this->countTrackedItems();
    $this->assertEqual($tracked_items, 0, t('After disabling the index, no items should be tracked'));

    // Go to the index edit path
    $settings_path = 'admin/config/search/search-api/index/' . $this->indexId . '/edit';
    $this->drupalGet($settings_path);
    // Enable the index
    $edit = array(
      'status' => TRUE,
      'datasourcePluginConfigs[entity:node][default]' => 0,
      'datasourcePluginConfigs[entity:node][bundles][article]' => TRUE,
      'datasourcePluginConfigs[entity:node][bundles][page]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $tracked_items = $this->countTrackedItems();
    $this->assertNotNull($tracked_items, t('After enabling the index, at least 1 item should be tracked'));
  }

  /**
   * Changes datasources from an index and checks if it reacts accordingly.
   *
   * The expected behavior is such that, when an index changes the
   * datasource configurations, the tracker should remove all items from the
   * datasources it no longer needs to handle and add the new ones.
   */
  protected function changeIndexDatasource() {
    /** @var $index \Drupal\search_api\Entity\Index */
    $index = entity_load('search_api_index', $this->indexId, TRUE);
    $index->reindex();

    $tracked_items = $this->countTrackedItems();
    $user_count = \Drupal::entityQuery('user')->count()->execute();
    $node_count = \Drupal::entityQuery('node')->count()->execute();

    // Go to the index edit path
    $settings_path = 'admin/config/search/search-api/index/' . $this->indexId . '/edit';
    $this->drupalGet($settings_path);
    // enable indexing of users
    $edit = array(
      'status' => TRUE,
      'datasourcePluginConfigs[entity:node][default]' => 0,
      'datasourcePluginConfigs[entity:node][bundles][article]' => TRUE,
      'datasourcePluginConfigs[entity:node][bundles][page]' => TRUE,
      'datasourcePluginIds[]' => array('entity:user', 'entity:node'),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $tracked_items = $this->countTrackedItems();
    $t_args = array(
      '!usercount' => $user_count,
      '!nodecount' => $node_count,
    );
    $this->assertEqual($tracked_items, $user_count+$node_count, t('After enabling user and nodes with respectively !usercount users and !nodecount nodes we should have the sum of those to index', $t_args));

    $this->drupalGet($settings_path);
    // Disable indexing of users
    $edit = array(
      'status' => TRUE,
      'datasourcePluginConfigs[entity:node][default]' => 0,
      'datasourcePluginConfigs[entity:node][bundles][article]' => TRUE,
      'datasourcePluginConfigs[entity:node][bundles][page]' => TRUE,
      'datasourcePluginIds[]' => array('entity:node'),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $tracked_items = $this->countTrackedItems();
    $t_args = array(
      '!nodecount' => $node_count,
    );
    $this->assertEqual($tracked_items, $node_count, t('After disabling user indexing we should only have !nodecount nodes to index', $t_args));
  }

  /**
   * Changes the server for an index and checks if it reacts accordingly.
   *
   * The expected behavior is such that, when an index changes the
   * server configurations, the tracker should remove all items from the
   * server it no longer is attached to and add the new ones.
   */
  protected function changeIndexServer() {

  }

}
