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
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class MulesoftAppEditForm.
 */
class MulesoftAppEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mulesoft_app_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $app_id = NULL) {
    $app_entity = ApiinfoEntity::load($app_id);
    if ($app_entity) {
      $form['#prefix'] = '<div id="edit-app-form">';
      $form['#suffix'] = '</div>';
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $form['mulesoft_app_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('App Name'),
        '#maxlength' => 255,
        '#size' => 64,
        '#weight' => '0',
        '#required' => TRUE,
        '#default_value' => $app_entity->getName(),
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
      $response->addCommand(new ReplaceCommand('#edit-app-form', $form));
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
    $values = $form_state->getValues();
    $app_name = $values['mulesoft_app_name'];
    if (preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $app_name)) {
      $form_state->setErrorByName("mulesoft_app_name", $this->t("Special characters not allowed."));
    }
    if (empty($app_name)) {
      $form_state->setErrorByName("mulesoft_app_name", $this->t("App name field is required."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $app_name = $values['mulesoft_app_name'];
    $app_id = $values['app_id'];
    $app_entity = ApiinfoEntity::load($app_id);
    $mulesoft_connector = \Drupal::service('mulesoft_app.sdk_connector');
    $access_token = $mulesoft_connector->getaccesstoken();
    $serialized_body = JSON::encode([
      'name' => $app_name,
    ]);
    $mulesoft_connector = \Drupal::service('mulesoft_app.sdk_connector');
    $mulesoft_config = \Drupal::config('mulesoft_app.auth');
    $client = $mulesoft_connector->gethttpclient();
    $baseurl = $mulesoft_config->get('baseurl');
    $org_id = $mulesoft_config->get('orgid');
    try {
      $appID = $app_entity->get("field_app_id")->value;
      $response = $client->put('https://' . $baseurl . '/apiplatform/repository/v2/organizations/' . $org_id . '/applications/' . $appID, [
        'body' => $serialized_body,
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $access_token,
        ],
      ]);
      $code = $response->getStatusCode();
      if ($code == 200) {
        $app_entity->setName($app_name);
        $app_entity->save();
        \Drupal::messenger()
          ->addMessage(t("App @app_name has been updated successfully.", ["@app_name" => $app_name]));
      }
    } catch (RequestException $e) {
      if ($e->hasResponse()) {
        $exception = (string) $e->getResponse()->getBody();
        $exception_arr = JSON::decode($exception);
        if (isset($exception_arr['message'])) {
          \Drupal::messenger()->addMessage($exception_arr['message'], 'error');
        }
        elseif (isset($exception_arr['errors'])) {
          \Drupal::messenger()
            ->addMessage($exception_arr['errors'][0]['message'], 'error');
        }
      }
    }
    $form_state->setRedirect("mulesoft_app.myappuserpage");
  }

}
