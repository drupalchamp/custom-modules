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
class GetappclientForm extends FormBase {

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


    $connecton = \Drupal::service('amazon_apigateway.sdk_connector');

    $cognitoidentity = $connecton->getCognitoIdentityProviderClient();


    $resultpoolclients = $cognitoidentity->listUserPoolClients([

      'UserPoolId' => 'us-east-2_pSLC1pRhv', // REQUIRED
    ]);


    $poolclientsdetails = [];
    foreach ($resultpoolclients['UserPoolClients'] as $resultpoolclients) {

      $poolclientsdetails[$resultpoolclients['ClientId']] = $resultpoolclients['ClientName'];


    }


    $form['poolclients'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('API'),
      '#options' => $poolclientsdetails,

    ];


    //var_dump($productMintentity);

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
    return 'customyapp_appclient_form';
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


  }


}
