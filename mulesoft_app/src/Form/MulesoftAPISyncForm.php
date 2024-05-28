<?php

namespace Drupal\mulesoft_app\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Class MulesoftAPISyncForm.
 */
class MulesoftAPISyncForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mulesoft_app.mulesoftapisync',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mulesoft_api_sync_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mulesoft_app.mulesoftapisync');
    $form['publish_nodes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Publish nodes?'),
      '#description' => $this->t('Please enable the checkbox if the nodes has to be published during creation.'),
    ];
    $form['create_mulesoft_nodes'] = [
      '#type' => 'submit',
      '#value' => $this->t('Sync Mulesoft APIs'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('mulesoft_app.mulesoftapisync')
      ->set('publish_nodes', $form_state->getValue('publish_nodes'))
      ->save();
    // Get the access token for login
    $mulesoft_connector = \Drupal::service('mulesoft_app.sdk_connector');
    $apis_data = $mulesoft_connector->getMulesoftAPIDetails();
    if ($apis_data) {
      $this->syncMulesoftAPINodes($apis_data);
    }
  }

  /**
   * @param $access_token
   *
   * @return mixed
   */
  public function getMulesoftGroupAPIs($access_token) {
    try {
      $mulesoft_connector = \Drupal::service('mulesoft_app.sdk_connector');
      $client = $mulesoft_connector->gethttpclient();
      $baseurl = $this->configFactory->get('mulesoft_app.auth')->get('baseurl');
      $orgid = $this->configFactory->get('mulesoft_app.auth')->get('orgid');
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
    } catch (Exception $e) {
      echo $e->getMessage();
    }
  }

  /**
   * @param $groupAPIsarray
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * Implements to create MuleSoft API nodes.
   */
  public function syncMulesoftAPINodes($groupAPIsarray) {
    $publish_nodes = $this->config('mulesoft_app.mulesoftapisync')
      ->get('publish_nodes');
    $nodesSynced = FALSE;
    foreach ($groupAPIsarray as $groupID => $grouptitle) {
      $nid = \Drupal::entityQuery('node')
        ->condition('type', 'mulesoft_api')
        ->condition('field_mulesoft_api_id', $groupID)
        ->execute();
      if (!$nid) {
        $nodesSynced = TRUE;
        $node = Node::create([
          'type' => 'mulesoft_api',
          'title' => $grouptitle['label'],
          'field_mulesoft_title' => $grouptitle['label'],
          'field_mulesoft_api_id' => $groupID,
          'field_mulesoft_asset_id' => $grouptitle['assetId'],
          'status' => $publish_nodes,
        ]);
        $node->save();
      }
      else {
        $node_load = Node::load(reset($nid));
        if ($node_load) {
          $nodesSynced = TRUE;
          $node_load->set("field_mulesoft_title", $grouptitle['label']);
          $node_load->set("field_mulesoft_asset_id", $grouptitle['assetId']);
          $node_load->set("status", $publish_nodes);
          $node_load->save();
        }
      }
    }
    if ($nodesSynced) {
      \Drupal::messenger()
        ->addMessage("All the API's are synced into Drupal");
    }
    else {
      \Drupal::messenger()
        ->addMessage("There are no API's to sync into Drupal");
    }
  }

}
