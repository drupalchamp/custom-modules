<?php

namespace Drupal\mulesoft_app\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\StatusMessages;

/**
 * Class ConnectionConfigForm.
 */
class AuthenticationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mulesoft_app.auth',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mulesoft_app_authentication_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mulesoft_app.auth');
    $form['baseurl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base url'),
      '#description' => $this->t(''),
      '#required' => TRUE,
      '#default_value' => $config->get('baseurl') ?? '',
      '#attributes' => ['autocomplete' => 'off'],
    ];
    $form['orgid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organization id'),
      '#description' => $this->t(''),
      '#required' => TRUE,
      '#default_value' => $config->get('orgid') ?? '',
      '#attributes' => ['autocomplete' => 'off'],
    ];
    $form['envid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Environment id'),
      '#description' => $this->t(''),
      '#required' => TRUE,
      '#default_value' => $config->get('envid') ?? '',
      '#attributes' => ['autocomplete' => 'off'],
    ];
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User Name'),
      '#description' => $this->t(''),
      '#required' => TRUE,
      '#default_value' => $config->get('username') ?? '',
      '#attributes' => ['autocomplete' => 'off'],
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#description' => $this->t(''),
      //      '#required' => TRUE,
      '#attributes' =>
        [
          'autocomplete' => 'off',
          //          'value' => $config->get('password') ?? '',
        ],
    ];
    $form['accesstokevalidtime'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access token valid Time'),
      '#description' => $this->t('Enter access token valid time in minutes'),
      '#required' => TRUE,
      '#default_value' => $config->get('accesstokevalidtime') ?? '30',
      '#attributes' => ['autocomplete' => 'off'],
    ];
    $form['xsrftoken'] = [
      '#type' => 'textfield',
      '#title' => $this->t('XSRF token valid Time'),
      '#description' => $this->t('Enter XSRF token valid time in hours'),
      '#required' => TRUE,
      '#default_value' => $config->get('xsrftoken') ?? '24',
      '#attributes' => ['autocomplete' => 'off'],
    ];
    $form['enable_groups'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Groups to create APP'),
      '#default_value' => $config->get('enable_groups') ? $config->get('enable_groups') : FALSE,
      '#attributes' => ['autocomplete' => 'off'],
    ];
    $form['settings']['test_connection'] = [
      '#type' => 'details',
      '#title' => $this->t('Test connection'),
      '#description' => $this->t('Send request using the given API credentials.'),
      '#open' => TRUE,
    ];
    $form['settings']['test_connection']['message'] = [
      '#type' => 'markup',
      '#markup' => '',
      '#prefix' => '<div id="test-connection">',
      '#suffix' => '</div>',
    ];
    $form['settings']['test_connection']['test_connection_submit'] = [
      '#type' => 'submit',
      '#executes_submit_callback' => FALSE,
      '#value' => $this->t('Send request'),
      '#ajax' => [
        'callback' => [get_class($this), 'testConnection'],
        'wrapper' => "test-connection",
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Waiting for response...'),
        ],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }


  /**
   * Ajax callback for test connection.
   */
  public static function testConnection(array $form, FormStateInterface $form_state) {
    $ordId = $form_state->getValue('orgid');
    $envid = $form_state->getValue('envid');
    $password = $form_state->getValue('password');
    $connecton = \Drupal::service('mulesoft_app.sdk_connector');
    $response = $connecton->testConnection($ordId, $envid, $password);
    if (isset($response['total']) && isset($response['assets'])) {
      \Drupal::messenger()->addStatus(t('Connection success.'));
    }
    else {
      \Drupal::messenger()->addError(t('Connection failure.'));
    }
    return $form['settings']['test_connection']['message'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $ordId = $form_state->getValue('orgid');
    $envid = $form_state->getValue('envid');
    $password = $form_state->getValue('password');
    $connecton = \Drupal::service('mulesoft_app.sdk_connector');
    $response = $connecton->testConnection($ordId, $envid, $password);
    if (!isset($response['total']) && !isset($response['assets'])) {
      $form_state->setErrorByName("orgid", $this->t('Connection failure.'));
    }
  }

  /**
   * @param null $string
   *
   * @return false|string
   * Implements to encrypt password.
   */
  private function encryptPassword($string = NULL) {
    $ciphering = "AES-256-CTR";
    $options = 0;
    $encryption_iv = '1234567891011121';
    $encryption_key = "HSBCDEVPORTAL";
    $encryption = openssl_encrypt($string, $ciphering, $encryption_key, $options, $encryption_iv);
    return $encryption;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $password = $form_state->getValue('password');
    if ($password) {
      $encryptedPassword = $this->encryptPassword($password);
      $this->config('mulesoft_app.auth')
        ->set('password', $encryptedPassword)
        ->save();
    }
    parent::submitForm($form, $form_state);
    $this->config('mulesoft_app.auth')
      ->set('baseurl', $form_state->getValue('baseurl'))
      ->set('orgid', $form_state->getValue('orgid'))
      ->set('envid', $form_state->getValue('envid'))
      ->set('username', $form_state->getValue('username'))
      ->set('accesstokevalidtime', $form_state->getValue('accesstokevalidtime'))
      ->set('xsrftoken', $form_state->getValue('xsrftoken'))
      ->set('enable_groups', $form_state->getValue('enable_groups'))
      ->save();

  }

}
