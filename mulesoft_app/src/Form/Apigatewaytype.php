<?php

namespace Drupal\mulesoft_app\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Apigatewaytype.
 */
class Apigatewaytype extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mulesoft_app.apigatewaytype',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mulesoft_app_apigatewaytype_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mulesoft_app.apigatewaytype');
    $form['typeofapigateway'] = [
      '#type' => 'radios',
      '#title' => $this->t('Type of Apigateway'),
      '#options' => [
        'amazonapigateway' => 'Amazon API Gateway',
        'mulesoftapigateway' => 'Mulesoft API Gateway',
      ],
      '#required' => TRUE,
      '#default_value' => $config->get('typeofapigateway') ?? 'default',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('mulesoft_app.apigatewaytype')
      ->set('typeofapigateway', $form_state->getValue('typeofapigateway'))
      ->save();
  }

}
