<?php

namespace Drupal\mulesoft_app\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\user\Entity\User;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class MulesoftAppCreateForm.
 */
class MulesoftAppCreateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mulesoft_app_create_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $user = User::load(\Drupal::currentUser()->id());
    if ($user) {
      $company = $user->get("field_company")->target_id;
      $isAdmin = $user->hasRole("administrator");
      if (!$company && !$isAdmin) {
        return new RedirectResponse("/system/403");
      }
    }
    $mulesoft_config = \Drupal::config('mulesoft_app.auth');
    $form['mulesoft_app_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App Name'),
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '0',
      '#required' => TRUE,
    ];
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'mulesoft_api')
      ->condition('status', 1)
      ->execute();
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    $mulesoft_group_apis = [];
    $field_instance = \Drupal::entityTypeManager()
      ->getStorage('field_config')
      ->load('apiinfo_entity.mulesoft_api_gateway_keys.field_user_group');
    $groups_selected = $field_instance->getSettings()['handler_settings']['target_bundles'];
    foreach ($nodes as $each_node) {
      $check = $each_node->access('view', $user);
      $node_app_id = $each_node->get("field_mulesoft_api_id")->value;
      $status = $each_node->get("status")->value;
      if ($check && $node_app_id && $status) {
        $instance_id = $each_node->get('field_mulesoft_api_id')
          ->getValue()[0]['value'];
        $link = $each_node->get('field_api_overview_link')->uri;
        $overview_link = '#';
        if ($link) {
          $overview_link = str_replace("internal:", "", $link);
          $overview_link = str_replace("entity:", "/", $overview_link);
          if (strpos($overview_link, 'node/') !== FALSE) {
            $overview_link = \Drupal::service('path_alias.manager')
              ->getAliasByPath($overview_link);
          }
        }
        $api_icon_id = $each_node->get("field_api_icon")->target_id;
        $api_icon_url = '';
        if ($api_icon_id) {
          $api_icon_url = file_create_url($each_node->field_api_icon->entity->getFileUri());;
        }
        if ($instance_id) {
          $mulesoft_group_apis[$instance_id] = [
            'title' => $each_node->getTitle(),
            'link' => $overview_link,
            'icon_url' => $api_icon_url,
          ];
        }
      }
    }
    $form['mulesoft_group_apis'] = [
      '#theme_wrappers' => ['my_custom_fieldset_mule_apis'],
      '#type' => 'checkboxes',
      '#title' => $this->t('API access'),
      '#options' => $mulesoft_group_apis,
      '#required' => TRUE,
      '#weight' => '0',
      '#description' => empty($mulesoft_group_apis) ? 'You do not have access to any apis. Please contact the administrator.' : '',
    ];
    $enable_groups = $mulesoft_config->get("enable_groups");
    if ($enable_groups) {
      $groups = [];
      $grp_membership_service = \Drupal::service('group.membership_loader');
      $grps = $grp_membership_service->loadByUser($user);
      foreach ($grps as $grp) {
        if (in_array($grp->getGroup()->bundle(), $groups_selected)) {
          $groups[$grp->getGroup()->id()] = $grp->getGroup()->label();
        }
      }
      if (empty($groups)) {
        \Drupal::messenger()
          ->addMessage("User is not associated to any group", 'error');
      }
      elseif (count($groups) == 1) {
        $form['mulesoft_user_group'] = [
          '#type' => 'hidden',
          '#value' => $grps[0]->getGroup()->id(),
        ];
      }
      elseif (count($groups) > 1) {
        $form['mulesoft_user_group'] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('User Groups'),
          '#options' => $groups,
          '#description' => 'Please select the group to which the user should be associated.',
        ];
      }
      $form['submit'] = [
        '#type' => 'submit',
        '#disabled' => $groups ? FALSE : TRUE,
        '#value' => $this->t('Create app'),
      ];
    }
    else {
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Create app'),
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Get the access token for login
    $mulesoft_connector = \Drupal::service('mulesoft_app.sdk_connector');
    $access_token = $mulesoft_connector->getaccesstoken();
    //    $group_ids_from_mulesoft = $this->getMulesoftApiGroups($access_token);
    // Validate for the valid api ids in the selected apis.
    $app_ids_from_mulesoft = $mulesoft_connector->getMulesoftAPIDetails($access_token);
    $form_values = $form_state->getValues();
    $group_ids = $form_values['mulesoft_group_apis'];
    if ($app_ids_from_mulesoft) {
      $groupAPIsarrayflip = array_keys($app_ids_from_mulesoft);
      foreach ($group_ids as $each_group_id => $each_groupValue) {
        if ($each_groupValue) {
          if (!in_array($each_groupValue, $groupAPIsarrayflip)) {
            $form_state->setErrorByName('', 'Invalid API. Not able to create the app.');
          }
        }
      }
    }
    $app_name = $form_values['mulesoft_app_name'];
    $query = \Drupal::entityQuery('apiinfo_entity');
    $query->condition('name', $app_name);
    $app_exists = $query->execute();
    if ($app_exists) {
      $form_state->setErrorByName('mulesoft_app_name', $this->t('App <b>@app_name</b> already exists in your organization.', ["@app_name" => $app_name]));
    }
    $errors = $form_state->getErrors();
    if (empty($errors) && !$app_exists) {
      $user_group = isset($form_values['mulesoft_user_group']) ? $form_values['mulesoft_user_group'] : '';
      $filtered_group_ids = [];
      // Create the app.
      $app_create_response = $this->mulesoft_app_create_app($access_token, $app_name);
      $app_id = $app_create_response['id'];
      // Create contract with the app if the app is created.
      $contract_ids = [];
      if ($app_id && $group_ids) {
        foreach ($group_ids as $each_group_id) {
          if ($each_group_id) {
            // Fetch the nid of the api.
            $query = \Drupal::entityQuery('node');
            $query->condition('status', 1);
            $query->condition('type', 'mulesoft_api');
            $query->condition('field_mulesoft_api_id', $each_group_id);
            $entity_id = $query->execute();
            if ($entity_id) {
              $filtered_group_ids[] = array_pop($entity_id);
              $contract_ids[$each_group_id][] = $this->mulesoft_app_create_contract($access_token, $app_id, $each_group_id);
            }
          }
        }
        $field_api_contract_id = [];
        if ($contract_ids) {
          foreach ($contract_ids as $group_id => $each_contract_id) {
            $paragraph = Paragraph::create([
              'type' => 'mulesoft_contract_id',
              'field_mulesoft_api_id' => $group_id,
              'field_mulesoft_contract_id' => $each_contract_id,
            ]);
            $paragraph->save();
            $field_api_contract_id['field_api_contract_id'][] = [
              'target_id' => $paragraph->id(),
              'target_revision_id' => $paragraph->getRevisionId(),
            ];
          }
          // Create the app entity
          if ($filtered_group_ids) {
            $this->mulesoft_app_create_mulesoft_entity(
              $app_name,
              $app_id,
              $app_create_response['clientId'],
              $app_create_response['clientSecret'],
              $filtered_group_ids,
              $user_group,
              $field_api_contract_id
            );
          }
        }
      }
      else {
        $form_state->setErrorByName('', 'There was an error creating the app. Please contact the administrator.');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $app_name = $form_state->getValues()['mulesoft_app_name'];
    \Drupal::messenger()->addMessage("App '$app_name' has been created.");
    $url = Url::fromUri('internal:/myapp');
    $form_state->setRedirectUrl($url);
  }

  /**
   * @param $client
   * @param $mulesoft_host
   * @param $access_token
   * @param $app_name
   *
   * @return mixed
   */
  public function mulesoft_app_create_app($access_token, $app_name) {
    $serialized_body = JSON::encode([
      'name' => $app_name,
    ]);
    $mulesoft_connector = \Drupal::service('mulesoft_app.sdk_connector');
    $mulesoft_config = \Drupal::config('mulesoft_app.auth');
    $client = $mulesoft_connector->gethttpclient();
    $baseurl = $mulesoft_config->get('baseurl');
    $org_id = $mulesoft_config->get('orgid');
    try {
      $response = $client->post('https://' . $baseurl . '/apiplatform/repository/v2/organizations/' . $org_id . '/applications', [
        'body' => $serialized_body,
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $access_token,
        ],
      ]);
      $code = $response->getStatusCode();
      if ($code == 201) {
        if ($response->getBody()) {
          $response_body = JSON::decode($response->getBody());
          return $response_body;
        }
      }
    } catch (RequestException $e) {
      if ($e->hasResponse()) {
        $exception = (string) $e->getResponse()->getBody();
        $exception_arr = JSON::decode($exception);
        if (isset($exception_arr['message'])) {
          \Drupal::logger('mulesoft_app')->error($exception_arr['message']);
          \Drupal::messenger()->addMessage($exception_arr['message'], 'error');
        }
        elseif (isset($exception_arr['errors'])) {
          \Drupal::logger('mulesoft_app')
            ->error($exception_arr['errors'][0]['message']);
          \Drupal::messenger()
            ->addMessage($exception_arr['errors'][0]['message'], 'error');
        }
      }
    }
  }

  /**
   * @param $client
   * @param $access_token
   * @param $app_id
   */
  public function mulesoft_app_create_contract($access_token, $app_id, $api_id) {
    $serialized_body = JSON::encode([
      'applicationId' => $app_id,
      'partyId' => '',
      'partyName' => '',
      'acceptedTerms' => 'false',
    ]);
    $mulesoft_connector = \Drupal::service('mulesoft_app.sdk_connector');
    $mulesoft_config = \Drupal::config('mulesoft_app.auth');
    $client = $mulesoft_connector->gethttpclient();
    $baseurl = $mulesoft_config->get('baseurl');
    $org_id = $mulesoft_config->get('orgid');
    $env_id = trim($mulesoft_config->get('envid'));
    $host_url = 'https://' . $baseurl . '/apimanager/api/v1/organizations/' . $org_id . '/environments/' . $env_id . '/apis/' . $api_id . '/contracts';
    try {
      $response = $client->post($host_url, [
        'body' => $serialized_body,
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $access_token,
        ],
      ]);
      $code = $response->getStatusCode();
      if ($code == 201) {
        if ($response->getBody()) {
          $response_body = JSON::decode($response->getBody());
          return $response_body['id'];
        }
      }
    } catch (RequestException $e) {
      if ($e->hasResponse()) {
        $exception = (string) $e->getResponse()->getBody();
        $exception_arr = JSON::decode($exception);
        if (property_exists($exception_arr, 'message')) {
          \Drupal::logger('mulesoft_app')->error($exception_arr->message);
          \Drupal::messenger()->addMessage($exception_arr->message, 'error');
        }
        elseif (property_exists($exception_arr, 'errors')) {
          \Drupal::logger('mulesoft_app')
            ->error($exception_arr->errors[0]->message);
          \Drupal::messenger()
            ->addMessage($exception_arr->errors[0]->message, 'error');
        }
      }
    }
  }

  public function mulesoft_app_create_mulesoft_entity($app_name, $app_id, $clientId, $clientSecret, $filtered_group_ids, $user_group, $field_api_contract_id) {
    $user = \Drupal::currentUser();
    $fields = [
      'type' => 'mulesoft_api_gateway_keys',
      'name' => $app_name,
      'field_app_owner' => $user->id(),
      'field_client_id' => $clientId,
      'field_client_secret' => $clientSecret,
      'field_app_id' => $app_id,
      'field_mulesoft_apis' => $filtered_group_ids,
      'field_api_contract_id' => $field_api_contract_id['field_api_contract_id'],
    ];
    if ($user_group) {
      $fields['field_user_group'] = $user_group;
    }
    $entity = \Drupal::entityTypeManager()
      ->getStorage('apiinfo_entity')
      ->create($fields);
    $entity->save();
  }

  public function getMulesoftApiGroups($access_token) {
    try {
      $mulesoft_connector = \Drupal::service('mulesoft_app.sdk_connector');
      $mulesoft_config = \Drupal::config('mulesoft_app.auth');
      $client = $mulesoft_connector->gethttpclient();
      $baseurl = $mulesoft_config->get('baseurl');
      $orgid = $mulesoft_config->get('orgid');
      $response = $client->get('https://' . $baseurl . '/apimanager/api/v1/organizations/' . $orgid . '/groups', [
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $access_token,
        ],
      ]);

      $code = $response->getStatusCode();
      if ($code == 200) {
        if ($response->getBody()) {
          $response_body = JSON::decode($response->getBody());
          return $response_body;
        }
      }
    } catch (RequestException $e) {
      if ($e->hasResponse()) {
        $exception = (string) $e->getResponse()->getBody();
        $exception_arr = JSON::decode($exception);
        if (property_exists($exception_arr, 'message')) {
          \Drupal::logger('mulesoft_app')->error($exception_arr->message);
          \Drupal::messenger()->addMessage($exception_arr->message, 'error');
        }
        elseif (property_exists($exception_arr, 'errors')) {
          \Drupal::logger('mulesoft_app')
            ->error($exception_arr->errors[0]->message);
          \Drupal::messenger()
            ->addMessage($exception_arr->errors[0]->message, 'error');
        }
      }
    }
  }

}
