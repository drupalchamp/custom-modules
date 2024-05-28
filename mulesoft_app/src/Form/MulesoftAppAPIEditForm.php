<?php

namespace Drupal\mulesoft_app\Form;

use Drupal\apiinfo\Entity\ApiinfoEntity;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class MulesoftAppAPIEditForm.
 */
class MulesoftAppAPIEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mulesoft_app_api_access_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $app_id = NULL) {
    $app_entity = ApiinfoEntity::load($app_id);
    if ($app_entity) {
      $user = \Drupal::currentUser();
      $apis_selected = $app_entity->get("field_mulesoft_apis")->getValue();
      $selected_apis = [];
      if ($apis_selected) {
        foreach ($apis_selected as $api_selected) {
          $api_selected_id = $api_selected['target_id'];
          $node_load = Node::load($api_selected_id);
          if ($node_load) {
            $api_id = $node_load->get("field_mulesoft_api_id")->value;
            $selected_apis[] = $api_id;
          }
        }
      }
      $form['selected_apis'] = [
        '#type' => 'hidden',
        '#value' => $selected_apis,
      ];
      $form['#prefix'] = '<div id="edit-app-api-form">';
      $form['#suffix'] = '</div>';
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $nids = \Drupal::entityQuery('node')
        ->condition('type', 'mulesoft_api')
        ->condition('status', 1)
        ->execute();
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $nodes = $node_storage->loadMultiple($nids);
      $mulesoft_group_apis = [];
      foreach ($nodes as $each_node) {
        $check = $each_node->access('view', $user);
        $node_app_id = $each_node->get("field_mulesoft_api_id")->value;
        if ($check && $node_app_id) {
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
        '#default_value' => $selected_apis,
        '#description' => empty($mulesoft_group_apis) ? 'You do not have access to any apis. Please contact the administrator.' : '',
      ];
      $form['app_id'] = [
        '#type' => 'hidden',
        '#value' => $app_id,
      ];
      $form['submit'] = [
        '#type' => 'submit',
        '#attributes' => [
          'class' => [
            'use-ajax',
          ],
        ],
        '#ajax' => [
          'callback' => [$this, 'setMessage'],
          'event' => 'click',
        ],
        '#value' => $this->t('Save'),
      ];
    }
    else {
      \Drupal::messenger()->addMessage("App not found", 'error');
      return new RedirectResponse("/myapp");
    }
    return $form;
  }

  /**
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   * Implements to validate reason.
   */
  function setMessage(&$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $response = new AjaxResponse();
    if ($form_state->hasAnyErrors()) {
      $form_state->setRebuild(TRUE);
      $response->addCommand(new ReplaceCommand('#edit-app-api-form', $form));
    }
    else {
      $values = $form_state->getValues();
      $appID = $values['app_id'];
      $command = new RedirectCommand("/myapp/$appID/details");
      return $response->addCommand($command);
    }
    return $response;
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach ($values['mulesoft_group_apis'] as $each_group_id) {
      if ($each_group_id) {
        $query = \Drupal::entityQuery('node');
        $query->condition('status', 1);
        $query->condition('type', 'mulesoft_api');
        $query->condition('field_mulesoft_api_id', $each_group_id);
        $entity_id = $query->execute();
        if ($entity_id) {
          $filtered_group_ids[] = array_pop($entity_id);
        }
      }
    }
    $app_id = $values['app_id'];
    $app_entity = ApiinfoEntity::load($app_id);
    $appID = $app_entity->get("field_app_id")->value;
    $api_contract_arr = [];
    $api_contract_id = $app_entity->get('field_api_contract_id')->getValue();
    $original_apis_arr = $app_entity->get('field_mulesoft_apis')->getValue();
    $original_apis = [];
    foreach ($original_apis_arr as $each_api) {
      $original_apis[] = $each_api['target_id'];
    }
    $delete_original_apis = array_diff($original_apis, $filtered_group_ids);
    $add_apis = array_diff($filtered_group_ids, $original_apis);
    $mulesoft_connector = \Drupal::service('mulesoft_app.sdk_connector');
    $access_token = $mulesoft_connector->getaccesstoken();
    if ($delete_original_apis) {
      foreach ($api_contract_id as $paragraph_key => $each_api_contract_id) {
        $p = Paragraph::load($each_api_contract_id['target_id']);
        if ($p) {
          $api_id = $p->get('field_mulesoft_api_id')->getValue()[0]['value'];
          $contract_id = $p->get('field_mulesoft_contract_id')
            ->getValue()[0]['value'];
          if ($contract_id) {
            $api_contract_arr[$api_id] = $contract_id;
            $paragraphs[$contract_id] = $each_api_contract_id['target_id'];
          }
          else {
            $p->delete();
          }
        }
      }
      foreach ($delete_original_apis as $original_api) {
        $api_node = Node::load($original_api);
        if ($api_node) {
          $api_group_id = $api_node->get('field_mulesoft_api_id')->value;
          $api_asset_id = $api_node->get('field_mulesoft_asset_id')->value;
          if (isset($api_contract_arr[$api_group_id])) {
            $contractId = $api_contract_arr[$api_group_id];
            if ($contractId) {
              $contractRevoke = mulesoft_app_revoke_api_group_contract($api_group_id, $contractId, $api_asset_id);
              if ($contractRevoke) {
                $contractdelete = mulesoft_app_delete_api_group_contract($api_group_id, $contractId, $api_asset_id);
                if ($contractdelete) {
                  $paragraph_load = Paragraph::load($paragraphs[$contractId]);
                  $paragraph_load->set("field_paragraph_deleted", 1);
                  $paragraph_load->save();
                }
              }
            }
          }
        }
      }
    }
    $contract_ids = [];
    if ($add_apis) {
      foreach ($add_apis as $filtered_group_id) {
        $api_node = Node::load($filtered_group_id);
        if ($api_node) {
          $api_group_id = $api_node->get('field_mulesoft_api_id')->value;
          $contract_id = $this->mulesoft_app_create_contract($access_token, $appID, $api_group_id);
          if ($contract_id) {
            $contract_ids[$api_group_id][] = $contract_id;
          }
        }
      }
      if ($contract_ids) {
        foreach ($contract_ids as $group_id => $each_contract_id) {
          if ($each_contract_id) {
            $paragraph = Paragraph::create([
              'type' => 'mulesoft_contract_id',
              'field_mulesoft_api_id' => $group_id,
              'field_mulesoft_contract_id' => $each_contract_id,
            ]);
            $paragraph->save();
            $app_entity->get("field_api_contract_id")->appendItem($paragraph);
          }
        }
      }
    }
    if ($filtered_group_ids) {
      $app_entity->set('field_mulesoft_apis', []);
      $app_entity->save();
      $index = 0;
      foreach ($filtered_group_ids as $filtered_group_id) {
        $api_node = Node::load($filtered_group_id);
        if ($api_node) {
          if ($index == 0) {
            $app_entity->set('field_mulesoft_apis', $filtered_group_id);
            $app_entity->save();
          }
          else {
            $app_entity->get("field_mulesoft_apis")->appendItem($api_node);
          }
          $index++;
        }
      }
    }
    $app_entity->save();
    $app_name = $app_entity->label();
    \Drupal::messenger()
      ->addMessage(t('@appname app has been updated successfully.', ['@appname' => $app_name]));
  }

  /**
   * @param $client
   * @param $access_token
   * @param $app_id
   */
  public
  function mulesoft_app_create_contract($access_token, $app_id, $api_id) {
    $serialized_body = JSON::encode([
      'applicationId' => (int) $app_id,
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
        if (isset($exception_arr['message'])) {
          \Drupal::logger('mulesoft_app')->error($exception_arr['message']);
          \Drupal::messenger()->addMessage($exception_arr['message'], 'error');
        }
      }
    }
  }

}
