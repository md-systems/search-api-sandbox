<?php

/**
 * @file
 * Contains \Drupal\search_api\Index\IndexInterface.
 */

namespace Drupal\search_api\Index;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Server\ServerInterface;

/**
 * Defines the interface for index entities.
 */
interface IndexInterface extends ConfigEntityInterface {

  /**
   * String used to separate a datasource prefix from the rest of an identifier.
   *
   * Internal field identifiers of datasource-dependent fields in the Search API
   * consist of two parts: the ID of the datasource to which the field belongs;
   * and the property path to the field, with properties separated by colons.
   * The two parts are concatenated using this character as a separator to form
   * the complete field identifier. (In the case of datasource-independent
   * fields, the identifier doesn't contain the separator.)
   *
   * Likewise, internal item IDs consist of the datasource ID and the item ID
   * within that datasource, separated by this character.
   */
  const DATASOURCE_ID_SEPARATOR = '|';

  /**
   * Retrieves the index description.
   *
   * @return string
   *   The description of this index.
   */
  public function getDescription();

  /**
   * Determines whether this index is read-only.
   *
   * @return bool
   *   TRUE if this index is read-only, otherwise FALSE.
   */
  public function isReadOnly();

  /**
   * Gets the cache ID prefix used for this index's caches.
   *
   * @param $type
   *   The type of cache. Currently only "fields" is used.
   *
   * @return
   *   The cache ID (prefix) for this index's caches.
   */
  public function getCacheId($type = 'fields');

  /**
   * Retrieves an option.
   *
   * @param string $name
   *   The name of an option.
   * @param mixed $default
   *   The value return if the option wasn't set.
   *
   * @return mixed
   *   The value of the option.
   */
  public function getOption($name, $default = NULL);

  /**
   * Retrieves an array of all options.
   *
   * @return array
   *   An associative array of option values, keyed by the option name.
   */
  public function getOptions();

  /**
   * Sets the options.
   *
   */
  public function setOptions($options);

  /**
   * Sets an option.
   *
   * @param string $name
   *   The name of an option.
   * @param $option
   *   The new option.
   *
   * @return mixed
   *   The value of the option.
   */
  public function setOption($name, $option);

  /**
   * Retrieves the IDs of all datasources enabled for this index.
   *
   * @return string[]
   *   The IDs of the datasource plugins used by this index.
   */
  public function getDatasourceIds();

  /**
   * Determines whether the given datasource ID is valid for this index.
   *
   * The general contract of this method is that it should return TRUE if, and
   * only if, a call to getDatasource() with the same ID would not result in an
   * exception.
   *
   * @param string $datasource_id
   *   A datasource plugin ID.
   *
   * @return bool
   *   TRUE if the datasource with the given ID is enabled for this index and
   *   can be loaded. FALSE otherwise.
   */
  public function isValidDatasource($datasource_id);

  /**
   * Retrieves a specific datasource plugin for this index.
   *
   * @param string $datasource_id
   *   The ID of the datasource plugin to return.
   *
   * @return \Drupal\search_api\Datasource\DatasourceInterface
   *   The datasource plugin with the given ID.
   *
   * @throws \Drupal\search_api\Exception\SearchApiException
   *   If the specified datasource isn't enabled for this index, or couldn't be
   *   loaded.
   */
  public function getDatasource($datasource_id);

  /**
   * Retrieves this index's datasource plugins.
   *
   * @return \Drupal\search_api\Datasource\DatasourceInterface[]
   *   The datasource plugins used by this index, keyed by plugin ID.
   */
  public function getDatasources();

  /**
   * Determine whether the tracker is valid.
   *
   * @return bool
   *   TRUE if the tracker is valid, otherwise FALSE.
   */
  public function hasValidTracker();

  /**
   * Retrieves the tracker plugin's ID.
   *
   * @return string
   *   The ID of the tracker plugin used by this index.
   */
  public function getTrackerId();

  /**
   * Retrieves the tracker plugin.
   *
   * @return \Drupal\search_api\Tracker\TrackerInterface
   *   An instance of TrackerInterface.
   *
   * @throws \Drupal\search_api\Exception\SearchApiException
   *   If the tracker couldn't be instantiated.
   */
  public function getTracker();

  /**
   * Determines whether the server is valid.
   *
   * @return bool
   *   TRUE if the server is valid, otherwise FALSE.
   */
  public function hasValidServer();

  /**
   * Checks if this index has an enabled server.
   *
   * @return bool
   *   TRUE if this index is attached to a valid, enabled server.
   */
  public function isServerEnabled();

  /**
   * Retrieves the ID of the server the index is attached to.
   *
   * @return string|null
   *   The index's server's machine name, or NULL if the index doesn't have a
   *   server.
   */
  public function getServerId();

  /**
   * Retrieves the server the index is attached to.
   *
   * @return \Drupal\search_api\Server\ServerInterface|null
   *   An instance of ServerInterface, or NULL if the index doesn't have a
   *   server.
   *
   * @throws \Drupal\search_api\Exception\SearchApiException
   *   If the server couldn't be loaded.
   */
  public function getServer();

  /**
   * Sets the server the index is attached to
   *
   * @param \Drupal\search_api\Server\ServerInterface|null $server
   *   The server to move this index to, or NULL.
   */
  public function setServer(ServerInterface $server = NULL);

  /**
   * Loads all enabled processors for this index in proper order.
   *
   * @param bool $all
   *   Also include non-active processors
   * @param string $sortBy
   *
   * @return \Drupal\search_api\Processor\ProcessorInterface[]
   *   All enabled processors for this index, as
   *   \Drupal\search_api\Plugin\search_api\ProcessorInterface objects.
   */
  public function getProcessors($all = FALSE, $sortBy = 'weight');

  /**
   * Preprocesses data items for indexing.
   *
   * Lets all enabled processors for this index preprocess the indexed data.
   *
   * @param array $items
   *   An array of items to be preprocessed for indexing.
   */
  public function preprocessIndexItems(array &$items);

  /**
   * Preprocesses a search query.
   *
   * Lets all enabled processors for this index preprocess the search query.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The object representing the query to be executed.
   */
  public function preprocessSearchQuery(QueryInterface $query);

  /**
   * Postprocesses search results before display.
   *
   * If a class is used for both pre- and post-processing a search query, the
   * same object will be used for both calls (so preserving some data or state
   * locally is possible).
   *
   * @param array $response
   *   An array containing the search results. See
   *   \Drupal\search_api\Plugin\search_api\QueryInterface::execute() for the
   *   detailed format.
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The object representing the executed query.
   */
  public function postprocessSearchResults(array &$response, QueryInterface $query);

  /**
   * Returns a list of all known fields for one datasource of this index.
   *
   * @param bool $only_indexed
   *   (optional) Return only indexed fields, not all known fields.
   * @param bool $get_additional
   *   (optional) Return not only known/indexed fields, but also related
   *   entities whose fields could additionally be added to the index.
   *
   * @return array
   *   An array of all known fields for this index. Keys are the field
   *   identifiers, the values are arrays for specifying the field settings. The
   *   structure of those arrays looks like this:
   *   - name: The human-readable name for the field.
   *   - name_prefix: A human-readable name prefix specifying the datasource.
   *   - description: A description of the field, if available.
   *   - indexed: Boolean indicating whether the field is indexed or not.
   *   - type: The type set for this field. One of the types returned by
   *     search_api_default_field_types().
   *   - datasource: The datasource to which this field belongs, or NULL if it
   *     is datasource-independent.
   *   - real_type: (optional) If a custom data type was selected for this
   *     field, this type will be stored here, and "type" contain the fallback
   *     default data type.
   *   - boost: A boost value for terms found in this field during searches.
   *     Usually only relevant for fulltext fields.
   *   If $get_additional is TRUE, this array is encapsulated in another
   *   associative array, which contains the above array under the "fields" key,
   *   and a list of related entities under the "additional fields" key,
   *   mapping field keys to arrays with the keys "name" and "enabled".
   */
  public function getFields($only_indexed = TRUE, $get_additional = FALSE);

  /**
   * Convenience method for getting all of this index's fulltext fields.
   *
   * @param bool $only_indexed
   *   (optional) If set to TRUE, only the indexed fulltext fields will be
   *   returned. Otherwise, this method will return all available fulltext
   *   fields.
   *
   * @return array
   *   An array containing all (or all indexed) fulltext fields defined for this
   *   index.
   */
  public function getFulltextFields($only_indexed = TRUE);

  /**
   * Retrieves the properties available for this index.
   *
   * @param string $datasource_id
   *   The ID of the datasource for which the properties should be retrieved.
   * @param bool $alter
   *   (optional) Whether to pass the property definitions to the index's
   *   enabled processors for altering before returning them.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface[]
   *   The properties included in this index, defined by the datasource and
   *   optionally altered by the enabled processors.
   *
   * @throws \Drupal\search_api\Exception\SearchApiException
   *   If the specified datasource isn't enabled for this index, or couldn't be
   *   loaded.
   */
  public function getPropertyDefinitions($datasource_id, $alter = TRUE);

  /**
   * Loads a single search item for this index.
   *
   * @param string $item_id
   *   The internal ID of the item, with datasource prefix.
   *
   * @return object|null
   *   The loaded item, or NULL if the item does not exist.
   */
  public function loadItem($item_id);

  /**
   * Loads multiple search items for this index.
   *
   * @param array $item_ids
   *   The internal IDs of the items, with datasource prefix.
   * @param bool $flat
   *   (optional) If set, items will be returned in a single, flat array,
   *   instead of grouped by datasource.
   *
   * @return array
   *   The loaded items. If $flat was set, a single-dimensional array mapping
   *   internal item IDs to the loaded items. Otherwise, an array mapping
   *   datasource IDs to arrays of items (keyed by internal item ID) loaded for
   *   that datasource.
   */
  public function loadItemsMultiple(array $item_ids, $flat = FALSE);

  /**
   * Indexes a set amount of items.
   *
   * Will fetch the items to be indexed from the datasource and send them to
   * indexItems(). It will then mark all successfully indexed items as such in
   * the datasource.
   *
   * @param int $limit
   *   (optional) The maximum number of items to index.
   * @param string|null $datasource_id
   *   (optional) If specified, only items of the datasource with that ID are
   *   indexed.
   *
   * @return int
   *   The number of items successfully indexed.
   */
  public function index($limit = -1, $datasource_id = NULL);

  /**
   * Indexes items on this index.
   *
   * Will return the IDs of items that should be marked as indexed – i.e., items
   * that were either rejected from indexing or were successfully indexed.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface[] $items
   *   An array of items to be indexed, keyed by their item IDs.
   *
   * @return array
   *   The IDs of all items that should be marked as indexed.
   *
   * @throws \Drupal\search_api\Exception\SearchApiException
   *   If any error occurred during indexing.
   */
  public function indexItems(array $items);

  /**
   * Marks all items in this index for reindexing.
   *
   * @throws \Drupal\search_api\Exception\SearchApiException
   *   If the tracker couldn't be loaded, for example.
   */
  public function reindex();

  /**
   * Adds items from a specific datasource to the index.
   *
   * Note that this method receives datasource-specific item IDs as the
   * parameter, not containing the datasource prefix.
   *
   * @param string $datasource_id
   *   The ID of the datasource to which the items belong.
   * @param array $ids
   *   An array of datasource-specific item IDs.
   *
   * @return bool
   *   TRUE if the operation was successful, otherwise FALSE.
   */
  public function trackItemsInserted($datasource_id, array $ids);

  /**
   * Updates items from a specific datasource present in the index.
   *
   * Note that this method receives datasource-specific item IDs as the
   * parameter, not containing the datasource prefix.
   *
   * @param string $datasource_id
   *   The ID of the datasource to which the items belong.
   * @param array $ids
   *   An array of datasource-specific item IDs.
   *
   * @return bool
   *   TRUE if the operation was successful, otherwise FALSE.
   */
  public function trackItemsUpdated($datasource_id, array $ids);

  /**
   * Deletes items from the index.
   *
   * Note that this method receives datasource-specific item IDs as the
   * parameter, not containing the datasource prefix.
   *
   * @param string $datasource_id
   *   The ID of the datasource to which the items belong.
   * @param array $ids
   *   An array of datasource-specific items IDs.
   *
   * @return bool
   *   TRUE if the operation was successful, otherwise FALSE.
   */
  public function trackItemsDeleted($datasource_id, array $ids);

  /**
   * Clears all items in this index and marks them for reindexing.
   *
   * @throws \Drupal\search_api\Exception\SearchApiException
   *   If the server couldn't be loaded, for example.
   */
  public function clear();

  /**
   * Resets the static and stored caches associated with this index.
   */
  public function resetCaches();

  /**
   * Creates a query object for this index.
   *
   * @param array $options
   *   (optional) Associative array of options configuring this query.
   *
   * @return \Drupal\search_api\Query\QueryInterface
   *   A query object for searching this index.
   *
   * @throws \Drupal\search_api\Exception\SearchApiException
   *   If the index is currently disabled or its server doesn't exist.
   *
   * @see \Drupal\search_api\Query\QueryInterface::create()
   */
  public function query(array $options = array());

  /**
   * Starts tracking for this index.
   */
  public function startTracking();

  /**
   * Stops tracking for this index.
   */
  public function stopTracking();

}
