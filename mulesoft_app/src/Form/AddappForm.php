<?php

namespace Drupal\mulesoft_app\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Url;

/**
 * Implements the AddappForm form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class AddappForm extends FormBase {

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
    $form['api'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('API'),
      '#options' => $productapi,
      '#required' => TRUE,
    ];
    $form['companyname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Company Name'),
    ];
    $form['businessemail'] = [
      '#type' => 'email',
      '#title' => $this->t('Business Email'),
    ];
    $form['socialsecuritynum'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Social Security Number'),
    ];
    $form['homeaddress'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Home Address'),
    ];
    $form['employeeraddress'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Employers Address'),
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
    return 'customyapp_addappform_form';
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
    $api = $form_state->getValue('api');
    // add paragraph
    foreach ($api as $eachapi) {
      if (empty($eachapi)) {
        continue;
      }
      switch ($eachapi) {
        case '16':
          if (empty($form_state->getValue('companyname'))) {
            $form_state->setErrorByName('companyname', $this->t('Company name is required'));
          }
          if (empty($form_state->getValue('businessemail'))) {
            $form_state->setErrorByName('businessemail', $this->t('Business email is required'));
          }
          if (!\Drupal::service('email.validator')
            ->isValid($form_state->getValue('businessemail'))) {
            $form_state->setErrorByName('businessemail', $this->t('Enter valid Business email id'));
          }
          break;
        case '3':
          if (empty($form_state->getValue('socialsecuritynum'))) {
            $form_state->setErrorByName('socialsecuritynum', $this->t('Social Security Number is required'));
          }
          if (empty($form_state->getValue('homeaddress'))) {
            $form_state->setErrorByName('homeaddress', $this->t('Home Address is required'));
          }
          if (strlen($form_state->getValue('homeaddress')) < 10) {
            // Set an error for the form element with a key of "title".
            $form_state->setErrorByName('homeaddress', $this->t('Home Address must be at least 10 characters long.'));
          }
          if (strlen($form_state->getValue('employeeraddress')) < 10) {
            // Set an error for the form element with a key of "title".
            $form_state->setErrorByName('employeeraddress', $this->t('Employers Address must be at least 10 characters long.'));
          }
          if (empty($form_state->getValue('employeeraddress'))) {
            $form_state->setErrorByName('employeeraddress', $this->t('Employers Address is required'));
          }
          break;
      }
    }
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
    $node = Node::create([
      'type' => 'my_app',
      'title' => $appname,
    ]);
    $node->uid = 1;
    $node->promote = 0;
    $node->sticky = 0;
    $node->field_user = $user->id();
    $node->field_callback_url = $callbackurl;
    $node->setPublished(FALSE);
    // add paragraph
    foreach ($api as $eachapi) {
      if (empty($eachapi)) {
        continue;
      }
      switch ($eachapi) {
        case '16':
          $node->field_company_name = $form_state->getValue('companyname');
          $node->field_business_email = $form_state->getValue('businessemail');
          break;
        case '3':
          $node->field_social_security_number = $form_state->getValue('socialsecuritynum');
          $node->field_home_address = $form_state->getValue('homeaddress');
          $node->field_employers_address = $form_state->getValue('employeeraddress');
          break;
      }
      $paragraph = Paragraph::create([
        'type' => 'api_keys',
        'field_api' => [
          'target_id' => $eachapi, // Here just provide referenced entity id.
        ],
      ]);
      $paragraph->save();
      $node->field_api_keys->appendItem($paragraph);
    }
    $node->save();
    $this->messenger()->addMessage($this->t('App is saved successfully.'));
    $url = Url::fromUri('internal:/myapp');
    $form_state->setRedirectUrl($url);
  }

}
