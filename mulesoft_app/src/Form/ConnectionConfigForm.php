<?php


namespace Drupal\mulesoft_app\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for changing connection related settings.
 */
class ConnectionConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mulesoft_app.client',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mulesoft_app_connection_config_form.';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['connect_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Connection timeout'),
      '#description' => $this->t('Number of seconds before an HTTP connection to Mulesoft API gateway is assumed to have timed out.'),
      '#default_value' => $this->config('mulesoft_app.client')
        ->get('http_client_connect_timeout'),
      '#min' => 0,
      '#step' => 0.1,
      '#required' => TRUE,
    ];

    $form['request_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Request timeout'),
      '#description' => $this->t('Number of seconds before an HTTP response from Mulesoft API gateway is assumed to have timed out.'),
      '#default_value' => $this->config('mulesoft_app.client')
        ->get('http_client_timeout'),
      '#min' => 0,
      '#step' => 0.1,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('mulesoft_app.client')
      ->set('http_client_connect_timeout', $form_state->getValue('connect_timeout'))
      ->set('http_client_timeout', $form_state->getValue('request_timeout'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
