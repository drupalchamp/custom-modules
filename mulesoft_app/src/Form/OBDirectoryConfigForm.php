<?php

namespace Drupal\mulesoft_app\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OBDirectoryConfigForm.
 */
class OBDirectoryConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mulesoft_app.obdirectoryconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'o_b_directory_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mulesoft_app.obdirectoryconfig');
    $form['ob_directory_get_api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OB Directory Get API URL'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('ob_directory_get_api_url'),
    ];
    $form['ob_directory_post_api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OB Create Directory POST API URL'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('ob_directory_post_api_url'),
    ];
    $form['ob_directory_add_certificate_url'] = [
      '#type' => 'textfield',
      '#maxlength' => 255,
      '#title' => $this->t('OB Directory Add Certificate API URL'),
      '#default_value' => $config->get('ob_directory_add_certificate_url'),
    ];
    $form['ob_directory_add_software_id_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OB Directory ADD Software ID URL'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('ob_directory_add_software_id_url'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('mulesoft_app.obdirectoryconfig')
      ->set('ob_directory_get_api_url', $form_state->getValue('ob_directory_get_api_url'))
      ->set('ob_directory_post_api_url', $form_state->getValue('ob_directory_post_api_url'))
      ->set('ob_directory_add_certificate_url', $form_state->getValue('ob_directory_add_certificate_url'))
      ->set('ob_directory_add_software_id_url', $form_state->getValue('ob_directory_add_software_id_url'))
      ->save();
  }

}
