<?php

/**
 * @file
 * Contains \Drupal\search_api\Form\ServerStatusForm.
 */

namespace Drupal\search_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\search_api\Server\ServerInterface;

/**
 * Form which allows basic operations on a server, e.g. clear indexed data.
 */
class ServerStatusForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_api_server_status';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, ServerInterface $server = NULL) {
    // Attach the server to the form.
    $form['#server'] = $server;

    // Allow authorized users to clear the indexed data on this server.
    $form['actions']['#type'] = 'actions';
    $form['actions']['clear'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Delete all indexed data on this server'),
      '#button_type' => 'danger',
    );
    return $form;
  }

  /**
   * {@inhertidoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // Redirect to the server clear page.
    $form_state['redirect_route'] = array(
      'route_name' => 'search_api.server_clear',
      'route_parameters' => array(
        'search_api_server' => $form['#server']->id(),
      ),
    );
  }

}
