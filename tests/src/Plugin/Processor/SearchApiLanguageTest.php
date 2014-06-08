<?php

/**
 * @file
 * Contains \Drupal\search_api\Tests\Plugin\Processor\SearchApiHighlightTest.
 */

namespace Drupal\search_api\Tests\Plugin\Processor;

use Drupal\Core\Language\Language as CoreLanguage;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\search_api\Plugin\SearchApi\Processor\Language;
use Drupal\search_api\Tests\Processor\TestItemsTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Language processor plugin.
 *
 * @group Drupal
 * @group search_api
 */
class SearchApiLanguageTest extends UnitTestCase {

  use TestItemsTrait;

  /**
   * Stores the processor to be tested.
   *
   * @var \Drupal\search_api\Plugin\SearchApi\Processor\Language
   */
  protected $processor;

  /**
   * A test index mock to use for tests.
   *
   * @var \Drupal\search_api\Index\IndexInterface
   */
  protected $index;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Highlight Processor Plugin',
      'description' => 'Unit tests of postprocessor excerpt highlighting.',
      'group' => 'Search API',
    );
  }

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp() {
    parent::setUp();

    $this->index = $this->getMock('Drupal\search_api\Index\IndexInterface');

    /** @var \Drupal\Core\StringTranslation\TranslationInterface $translation */
    $translation = $this->getStringTranslationStub();
    $this->processor = new Language(array(), 'search_api_language_processor', array());
    $this->processor->setStringTranslation($translation);
  }

  /**
   * Tests whether the "Item language" field is properly added to the index.
   *
   * @see \Drupal\search_api\Plugin\SearchApi\Processor\Language::alterPropertyDefinitions()
   */
  public function testAlterProperties() {
    // Tests whether the property gets properly added to the
    // datasource-independent properties.
    $properties = array();
    $this->processor->alterPropertyDefinitions($properties);
    $this->assertTrue(!empty($properties['search_api_language']), '"search_api_language" property got added.');
    if (!empty($properties['search_api_language'])) {
      $this->assertInstanceOf('Drupal\Core\TypedData\DataDefinitionInterface', $properties['search_api_language'], 'Added "search_api_language" property implements the necessary interface.');
      if ($properties['search_api_language'] instanceof DataDefinitionInterface) {
        $this->assertEquals($properties['search_api_language']->getLabel(), 'Item language', 'Correct label for "search_api_language" property.');
        $this->assertEquals($properties['search_api_language']->getDescription(), 'The language code of the item.', 'Correct description for "search_api_language" property.');
        $this->assertEquals($properties['search_api_language']->getDataType(), 'string', 'Correct type for "search_api_language" property.');
      }
    }

    // Tests whether the properties of specific datasources stay untouched.
    $properties = array();
    /** @var \Drupal\search_api\Datasource\DatasourceInterface $datasource */
    $datasource = $this->getMock('Drupal\search_api\Datasource\DatasourceInterface');
    $this->processor->alterPropertyDefinitions($properties, $datasource);
    $this->assertEmpty($properties, 'Datasource-specific properties did not get changed.');
  }

  /**
   * Tests whether the "Item language" field is properly added to indexed items.
   *
   * @see \Drupal\search_api\Plugin\SearchApi\Processor\Language::preprocessIndexItems()
   */
  public function testPreprocessIndexItems() {
    $fields = array(
      'search_api_language' => array(
        'type' => 'string',
      ),
    );
    $items = $this->createItems($this->index, 3, $fields);

    $object1 = $this->getMock('Drupal\Core\Entity\ContentEntityInterface');
    $object1->expects($this->any())
      ->method('language')
      ->will($this->returnValue(new CoreLanguage(array('id' => 'en'))));
    /** @var \Drupal\Core\Entity\ContentEntityInterface $object1 */
    $items[$this->item_ids[0]]->setOriginalObject($object1);

    $object2 = $this->getMock('Drupal\Core\Entity\ContentEntityInterface');
    $object2->expects($this->any())
      ->method('language')
      ->will($this->returnValue(new CoreLanguage(array('id' => 'es'))));
    /** @var \Drupal\Core\Entity\ContentEntityInterface $object2 */
    $items[$this->item_ids[1]]->setOriginalObject($object2);

    $object3 = $this->getMock('Drupal\search_api\Tests\TestComplexDataInterface');
    /** @var \Drupal\search_api\Tests\TestComplexDataInterface $object3 */
    $items[$this->item_ids[2]]->setOriginalObject($object3);

    $this->processor->preprocessIndexItems($items);

    $this->assertEquals(array('en'), $items[$this->item_ids[0]]->getField('search_api_language')->getValues(), 'The "Item language" value was correctly set for an English item.');
    $this->assertEquals(array('es'), $items[$this->item_ids[1]]->getField('search_api_language')->getValues(), 'The "Item language" value was correctly set for a Spanish item.');
    $this->assertEquals(array(CoreLanguage::LANGCODE_NOT_SPECIFIED), $items[$this->item_ids[2]]->getField('search_api_language')->getValues(), 'The "Item language" value was correctly set for a non-translatable item.');
  }

}
