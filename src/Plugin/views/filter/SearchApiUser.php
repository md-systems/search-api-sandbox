<?php

/**
 * @file
 * Contains SearchApiViewsHandlerFilterUser.
 */

namespace Drupal\search_api\Plugin\views\filter;

use Drupal\Component\Utility\Unicode;

/**
 * Views filter handler class for handling user entities.
 *
 * Based on views_handler_filter_user_name.
 *
 * @ViewsFilter("search_api_user")
 */
class SearchApiUserBase extends SearchApiFilterEntityBase {

  /**
   * {@inheritdoc}
   */
  public function valueForm(&$form, &$form_state) {
    parent::valueForm($form, $form_state);

    // Set autocompletion.
    $path = $this->isMultiValued() ? 'admin/views/ajax/autocomplete/user' : 'user/autocomplete';
    $form['value']['#autocomplete_path'] = $path;
  }

  /**
   * {@inheritdoc}
   */
  protected function idsToStrings(array $ids) {
    $names = array();
    $args[':uids'] = array_filter($ids);
    $result = db_query("SELECT uid, name FROM {users} u WHERE uid IN (:uids)", $args);
    $result = $result->fetchAllKeyed();
    foreach ($ids as $uid) {
      if (!$uid) {
        $names[] = \Drupal::config('user.settings')->get('anonymous');
      }
      elseif (isset($result[$uid])) {
        $names[] = $result[$uid];
      }
    }
    return implode(', ', $names);
  }

  /**
   * {@inheritdoc}
   */
  protected function validateEntityStrings(array &$form, array $values) {
    $uids = array();
    $missing = array();
    foreach ($values as $value) {
      if (Unicode::strtolower($value) === Unicode::strtolower(\Drupal::config('user.settings')->get('anonymous'))) {
        $uids[] = 0;
      }
      else {
        $missing[strtolower($value)] = $value;
      }
    }

    if (!$missing) {
      return $uids;
    }

    $result = db_query("SELECT * FROM {users} WHERE name IN (:names)", array(':names' => array_values($missing)));
    foreach ($result as $account) {
      unset($missing[strtolower($account->name)]);
      $uids[] = $account->uid;
    }

    if ($missing) {
      \Drupal::formBuilder()->setError($form, $form_state, \Drupal::translation()->formatPlural(count($missing), 'Unable to find user: @users', 'Unable to find users: @users', array('@users' => implode(', ', $missing))));
    }

    return $uids;
  }

}
