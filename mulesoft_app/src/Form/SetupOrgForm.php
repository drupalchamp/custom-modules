<?php

namespace Drupal\mulesoft_app\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\Entity\User;

/**
 * Class SetupOrgForm.
 */
class SetupOrgForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'setup_org_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $org_service = \Drupal::service('hsbc.org.service');
    $roles = $org_service->get_roles_from_ob();
    if ($org_service->is_org_added_in_directory()) {
      $message = ' Your Organisation is already registered. Useful links: <a href="/csrfile/upload">Upload your CSR </a>.';
      $rendered_message = \Drupal\Core\Render\Markup::create($message);
      $error_message = new TranslatableMarkup ('@message', ['@message' => $rendered_message]);
      \Drupal::messenger()->addMessage($error_message, 'error');
    }
    else {
      $current_user = \Drupal::currentUser();
      $user_id = $current_user->id();
      $user = User::load($user_id);
      $firstname = $user->get('first_name')->value;
      $lastname = $user->get('last_name')->value;
      $useremail = $user->getEmail();
      $form['technical_contact'] = [
        '#type' => 'fieldset',
        '#title' => $this
          ->t('Technical contact'),
      ];
      $form['technical_contact']['#prefix'] = '<div class="d-flex flex-column flex-md-row">';
      $form['technical_contact'] ['technical_contact_email'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Email'),
        '#description' => $this->t('Enter Email id'),
        '#required' => TRUE,
        '#default_value' => $useremail,
      ];
      $form['technical_contact'] ['technical_contact_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#description' => $this->t('Enter the Name'),
        '#required' => TRUE,
        '#default_value' => $firstname . ' ' . $lastname,
      ];
      $form['technical_contact']['technical_contact_phone'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Phone No'),
        '#description' => $this->t('Enter phone number'),
        '#required' => TRUE,
        '#default_value' => '',
      ];
      $form['business_contact'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Business contact'),
      ];
      $form['business_contact']['business_contact_email'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Email'),
        '#description' => $this->t('Enter Email id'),
        '#required' => TRUE,
        '#default_value' => $useremail,
      ];
      $form['business_contact']['business_contact_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#description' => $this->t('Enter the Name'),
        '#required' => TRUE,
        '#default_value' => $firstname . ' ' . $lastname,
      ];
      $form['business_contact']['business_contact_phone'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Phone No'),
        '#description' => $this->t('Enter phone number'),
        '#required' => TRUE,
        '#default_value' => '',
      ];
      $form['business_contact']['#suffix'] = '</div>';
      $countries = \Drupal::service('country_manager')->getList();
      $form['country'] = [
        '#type' => 'select',
        '#options' => ['' => 'Choose country'] + $countries,
        '#title' => $this->t('Country'),
        '#description' => $this->t('Select country'),
        '#required' => TRUE,
        '#default_value' => 'GB',
      ];
      $form['roles'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Roles'),
        '#options' => [
          'AISP' => $this->t('AISP - Accounts'),
          'PISP' => $this->t('PISP - Payments'),
          'CBPII' => $this->t('CBPII - Funds'),
        ],
        '#size' => 5,
        '#required' => TRUE,
      ];
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $errors = $form_state->getErrors();
    if (!$errors) {
      $org_service = \Drupal::service('hsbc.org.service');
      $org_id = $org_service->get_organization_id();
      if ($org_id) {
        $permissions = [
          'AISP' => $this->t('Accounts'),
          'PISP' => $this->t('Payments'),
          'CBPII' => $this->t('Funds'),
        ];
        $permission_str = 'Permission for ';
        $roles_arr = $form_state->getValues()['roles'];
        $roles = array_filter($roles_arr);
        $roles_str = implode(', ', $roles);
        if (count($roles) == 1) {
          $permission_str .= $permissions[array_pop($roles)];
        }
        else {
          if (count($roles) == 2) {
            $permission_str .= $permissions[array_pop($roles)] . ' and ' . $permissions[array_pop($roles)];
          }
          else {
            if (count($roles) == 3) {
              $permission_str .= implode(' and ', $permissions);
            }
          }
        }
        $current_date = date('d/m/y', strtotime('now'));
        $serialized_body = json_encode([
          'onboarded_to_open_banking' => 'true',
          'org_name' => $org_id,
          'passports' => [
            'OBSANDBOX' => [
              'permissions' => [
                [
                  'code' => 'P001',
                  'effective_from' => 'Current date in ' . $current_date,
                  'id' => 'uuid',
                  'permission' => $permission_str,
                ],
              ],
              'roles' => [
                $roles_str,
              ],
            ],

          ],
          'competent_authority_claims' => [
            'authorization_domain' => "Open Banking Sandbox",
            'authority_id' => "OBSANDBOX",
            'registration_id' => $org_id,
            'status' => 'Active',
            'authorizations' => [
              [
                'member_state' => 'GB',
                'roles' => [
                  $roles_str,
                ],
              ],
            ],
          ],
        ]);
        $client = \Drupal::httpClient();
        $ob_config = \Drupal::config('mulesoft_app.obdirectoryconfig');
        $create_directory_url = $ob_config->get('ob_directory_post_api_url');
        try {
          $response = $client->post($create_directory_url, [
            'body' => $serialized_body,
          ]);
          $code = $response->getStatusCode();
          if ($code == 201) {
            if ($response->getBody()) {
              $response_body = json_decode($response->getBody());
              return TRUE;
            }
          }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
          if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception_obj = json_decode($exception);
            if (property_exists($exception_obj, 'message')) {
              \Drupal::logger('mulesoft_app')->error($exception_obj->message);
              \Drupal::messenger()
                ->addMessage($exception_obj->message, 'error');
            }
            else {
              if (property_exists($exception_obj, 'errors')) {
                \Drupal::logger('mulesoft_app')
                  ->error($exception_obj->errors[0]->message);
                \Drupal::messenger()
                  ->addMessage($exception_obj->errors[0]->message, 'error');
              }
            }
          }
        }
      }
      else {
        $form_state->setErrorByName('', 'Not able to get the org id. Please contact the Administrator.');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::messenger()
      ->addMessage('The Organization is successfully registered in Open Banking Directory.');
  }

}
