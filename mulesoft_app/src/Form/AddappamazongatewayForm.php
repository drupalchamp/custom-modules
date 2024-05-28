<?php

namespace Drupal\mulesoft_app\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Url;
use Drupal\amazon_apigateway\Entity\App;


/**
 * Implements the AddappForm form controller.
 *
 *
 * @see \Drupal\Core\Form\FormBase
 */
class AddappamazongatewayForm extends FormBase {


  /**
   * Getter method for Form ID.
   *
   * The form ID is used in implementations of hook_form_alter() to allow other
   * modules to alter the render array built by this form controller. It must be
   * unique site wide. It normally starts with the providing module's name.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'customyapp_Addappamazongateway_form';
  }

  /**
   * @param array $form
   *   Default form array structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object containing current form state.
   *
   * @return array
   *   The render array defining the elements of the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {


    $form['appname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App Name'),
      '#description' => $this->t('App Name must be at least 5 characters in length.'),
      '#required' => TRUE,
    ];

    $form['callbackurl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Callback URL'),
      '#required' => FALSE,
    ];


    $result = \Drupal::entityQuery('commerce_product')
      ->condition('type', 'mint_products')
      ->condition('status', 1)
      ->execute();


    $productMintentity = \Drupal::entityTypeManager()
      ->getStorage('commerce_product')
      ->loadMultiple($result);


    $productapi = [];

    foreach ($productMintentity as $eachproductMintentity) {

      $productapi[$eachproductMintentity->id()] = $eachproductMintentity->getTitle();

    }

    $connecton = \Drupal::service('amazon_apigateway.sdk_connector');

    $awsconnection = $connecton->getClient();


    $awsresult = $awsconnection->getRestApis();


    $amazonapidetails = [];
    foreach ($awsresult['items'] as $eachapi) {
      $amazonapidetails[$eachapi['id']] = $eachapi['name'];
    }

    $form['api'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('API'),
      '#options' => $amazonapidetails,
      '#required' => TRUE,
    ];

    // Group submit handlers in an actions element with a key of "actions" so
    // that it gets styled correctly, and so that other modules may add actions
    // to the form. This is not required, but is convention.
    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }


  /**
   * Implements form validation.
   *
   * The validateForm method is the default method called to validate input on
   * a form.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $title = $form_state->getValue('appname');
    if (strlen($title) < 5) {
      // Set an error for the form element with a key of "title".
      $form_state->setErrorByName('appname', $this->t('The App Name must be at least 5 characters long.'));
    }

    // $api = $form_state->getValue('api');
  }

  /**
   * Implements a form submit handler.
   *
   * The submitForm method is the default method called for any submit elements.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /*
     * This would normally be replaced by code that actually does something
     * with the title.
     */
    $appname = $form_state->getValue('appname');
    $callbackurl = $form_state->getValue('callbackurl');
    $api = $form_state->getValue('api');


    $user = \Drupal::currentUser();

    if (empty($user->id())) {

      $this->messenger()
        ->addMessage($this->t('You have to be logged in to submit the form.'));
      return;

    }

    $appdetails = [];
    $connecton = \Drupal::service('amazon_apigateway.sdk_connector');

    $awsconnection = $connecton->getClient();

    $eachapiarray = [];

    foreach ($api as $eachapi) {

      if (empty($eachapi)) {
        continue;

      }

      $eachapiarray[] = $eachapi;

    }
    // create user pool client

    $cognitoidentity = $connecton->getCognitoIdentityProviderClient();

    $userpoolclient = $cognitoidentity->createUserPoolClient([

      'ClientName' => $appname, // REQUIRED
      'GenerateSecret' => TRUE,
      'AllowedOAuthFlows' => ['client_credentials'],
      'PreventUserExistenceErrors' => 'ENABLED',
      'UserPoolId' => 'us-east-2_pSLC1pRhv', // REQUIRED
      //'UserPoolId' => 'us-east-2_pSLC1pRhv', // REQUIRED

    ]);
    $apikeyamazon = $awsconnection->createApiKey([
      'customerId' => $user->getEmail(),
      'description' => 'Created for app ' . $appname . ' from marketplace',
      'enabled' => TRUE,
      'generateDistinctId' => TRUE,
      'name' => $appname,
      /* 'stageKeys'=> [
         [
           'restApiId'=> $eachapi,
           'stageName'=> \Drupal::config('amazon_apigateway.auth')->get('stagename')
         ],

       ],
      */

    ]);


    $awsconnection->createUsagePlanKey([
      'keyId' => $apikeyamazon['id'], // REQUIRED
      'keyType' => 'API_KEY', // REQUIRED
      'usagePlanId' => 'fl68y1', // REQUIRED
    ]);

    // Use the entity type manager (recommended).
    $entity = \Drupal::entityTypeManager()
      ->getStorage('apiinfo_entity')
      ->create(
        [
          'type' => 'amazon_api_gateway_keys',
          'name' => $appname,
          'field_callback_url' => $callbackurl,
          'field_app_owner' => $user->id(),
          'field_client_id' => $userpoolclient['UserPoolClient']['ClientId'],
          'field_client_secret' => $userpoolclient['UserPoolClient']['ClientSecret'],
          'field_user_pool_app_id' => $userpoolclient['UserPoolClient']['UserPoolId'],
          'field_api_key_id' => $apikeyamazon['id'],
          'field_api_key' => $apikeyamazon['value'],
          'field_apis' => $eachapiarray,

        ]);
    $entity->save();
    $this->messenger()->addMessage($this->t('App is saved successfully.'));

    //$form_state->setRedirectUrl('internal:'.'/myapp');
    //return;
    $url = Url::fromUri('internal:/myapp');
    $form_state->setRedirectUrl($url);
  }

}
