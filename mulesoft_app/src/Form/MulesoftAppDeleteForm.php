<?php

namespace Drupal\mulesoft_app\Form;

use Drupal\apiinfo\Entity\ApiinfoEntity;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form to delete APP user.
 *
 * @internal
 */
class MulesoftAppDeleteForm extends FormBase {

  public function getFormId() {
    return 'mulesoft_delete_app_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $app_id = NULL) {
    $current_path = \Drupal::service('path.current')->getPath();
    $current_path_explode = explode("/", $current_path);
    $app_load = ApiinfoEntity::load($current_path_explode[2]);
    $form['#prefix'] = '<div id="delete-app-form">';
    $form['#suffix'] = '</div>';
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];
    if ($app_load) {
      $unpublish = $app_load->get("field_unpublish")->value;
      if (!$unpublish) {
        $app_name = $app_load->getName();
        $appID = $app_load->get("field_app_id")->value;
        $form['app_name_markup'] = ['#markup' => $this->t('You’re going to delete <span class="hsbcmedium">@app_name</span>', ['@app_name' => $app_load->getName()])];
        $form['app_name'] = [
          '#type' => 'hidden',
          '#value' => $app_name,
        ];
        $form['app_id'] = [
          '#type' => 'hidden',
          '#value' => $current_path_explode[2],
        ];
        $form['appID'] = [
          '#type' => 'hidden',
          '#value' => $appID,
        ];
        $form['reason'] = [
          '#type' => 'textarea',
          '#attributes' => ['placeholder' => $this->t("Reason for Deletion")],
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
          '#value' => $this->t('Delete app'),
        ];
        return $form;
      }
    }
    else {
      \Drupal::messenger()->addMessage("App not found", 'error');
      return new RedirectResponse("/myapp");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $reason = $values['reason'];
    if (preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $reason)) {
      $form_state->setErrorByName("reason", $this->t("Special characters not allowed."));
    }
    if (!$reason) {
      $form_state->setErrorByName("reason", $this->t("Reason for Deletion field is required."));
    }
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
      $response->addCommand(new ReplaceCommand('#delete-app-form', $form));
    }
    else {
      $command = new RedirectCommand('/myapp');
      return $response->addCommand($command);
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $app_id = $values['app_id'];
    $app_name = $values['app_name'];
    $appID = $values['appID'];
    $mulesoft_connector = \Drupal::service('mulesoft_app.sdk_connector');
    $access_token = $mulesoft_connector->getaccesstoken();
    $client = $mulesoft_connector->gethttpclient();
    $mulesoft_config = \Drupal::config('mulesoft_app.auth');
    $baseurl = $mulesoft_config->get('baseurl');
    $org_id = $mulesoft_config->get('orgid');
    $host = 'https://' . $baseurl . '/apiplatform/repository/v2/organizations/' . $org_id . '/applications/' . $appID;
    try {
      $response = $client->delete($host, [
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $access_token,
        ],
      ]);
      $code = $response->getStatusCode();
      if ($code == 204) {
        if ($response->getBody()) {
          $date = date("Y-m-d\TH:i:s", strtotime("now"));
          $app_entity = ApiinfoEntity::load($app_id);
          $app_entity->set("field_unpublish", 1);
          $app_entity->set("field_comment", $values['reason']);
          $app_entity->set("field_unpublished_date", $date);
          $app_entity->save();
          \Drupal::messenger()
            ->addMessage(t('@appname app has been deleted successfully.', ['@appname' => $app_name]));
        }
      }
    } catch (RequestException $e) {
      if ($e->hasResponse()) {
        $exception = (string) $e->getResponse()->getBody();
        $exception = json_decode($exception);
        \Drupal::messenger()->addMessage($exception->message, 'error');
      }
    }
    // Redirect back to My Apps page.
    $form_state->setRedirect('mulesoft_app.myappuserpage');

  }

}
