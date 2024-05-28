<?php

declare(strict_types=1);

namespace Drupal\aes_settings\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Aes settings for this site.
 */
final class AesConfigSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'aes_settings_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['aes_settings.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('aes_settings.settings'); // Retrieve the configuration.

    $form['fieldset_data'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('AES SETTINGS'),
    );

    $form['fieldset_data']['aes_password'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create AES passwords'),
      '#description' => $this->t("Check this box if you would like for AES to start encrypting user passwords (and make them viewable to the roles with permission to do so). This is a process which normally will make more and more passwords AES-encrypted/readable over time since the AES module only can get an existing users password in plain text at certain moments, one such moment being when the user logs in. So you won't be able to view an existing users password until that user has logged in at least once after you checked this box. You may test this on yourself by logging out and in again, which should make your password appear on your user page."),
      '#default_value' => $config->get('aes_password'),
    ];

    $form['fieldset_data']['aes_implement'] = [
      '#type' => 'select',
      '#options' => [
        '0' => $this->t('Mcrypt extension'),                
      ], 
      '#title' => $this->t('AES implementation:'),
      '#description' => $this->t("The Mcrypt extension is the only installed implementation."),
      '#default_value' => $config->get('aes_implement') ?? '0',
    ];

    $form['fieldset_data']['aes_mfv_password'] = [
      '#type' => 'select',
      '#options' => [
        '0' => $this->t('Collapsible box'),
        '1' => $this->t('Own page'),
        '2' => $this->t('Both'),                
      ], 
      '#title' => $this->t('Method for viewing passwords:'),
      '#description' => $this->t("Wether to show the password as a collapsible box on the user info page (collapsed/hidden by default) or on a separate page with a tab on the user page, or both."),
      '#default_value' => $config->get('aes_mfv_password') ?? '0',
    ];

    $form['fieldset_data']['aes_cipher'] = [
      '#type' => 'select',
      '#options' => [
        '1' => $this->t('Rijndael 128'),
        '2' => $this->t('Rijndael 192'),
        '3' => $this->t('Rijndael 256'),                
      ], 
      '#title' => $this->t('Cipher:'),      
      '#default_value' => $config->get('aes_cipher') ?? '3',
    ];

    $form['fieldset_data']['aes_ks_method'] = [
      '#type' => 'select',
      '#options' => [
        '0' => $this->t('File'),
        '1' => $this->t('Database'),          
      ], 
      '#title' => $this->t('Key storage method:'),
      '#description' => $this->t("If possible, you should use the file storage method and assign a path below."),
      '#default_value' => $config->get('aes_ks_method')?? '0',
    ];

    $form['fieldset_data']['aes_pathtokey_file'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to keyfile:'),
      '#description' => $this->t("The path, including the filename, of the file in which to store your key. The access restrictions on this file should be set as high as possible while still allowing PHP read/write access."),
      '#default_value' => $config->get('aes_pathtokey_file') ?? 'keys/aes.key',
    ];

    $form['fieldset_data']['aes_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key:'),
      '#description' => $this->t("The key for your encryption system. You normally don't need to worry about this since this module will generate a key for you if none is specified. However you have the option of using your own custom key here."),
      '#default_value' => $config->get('aes_key'),
    ];

    $form['fieldset_data']['aes_confirm_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Confirm key:'),      
      '#default_value' => $config->get('aes_confirm_key'),
    ];

    return Parent::buildform($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
      $config = $this->config('aes_settings.settings'); // Retrieve the configuration.
      // Set the submitted configuration setting.
      $config->set("aes_password", $form_state->getvalue('aes_password'));
      $config->set("aes_implement", $form_state->getvalue('aes_implement'));
      $config->set("aes_mfv_password", $form_state->getvalue('aes_mfv_password'));
      $config->set("aes_cipher", $form_state->getvalue('aes_cipher'));
      $config->set("aes_ks_method", $form_state->getvalue('aes_ks_method'));
      $config->set("aes_pathtokey_file", $form_state->getvalue('aes_pathtokey_file'));
      $config->set("aes_key", $form_state->getvalue('aes_key'));
      $config->set("aes_confirm_key", $form_state->getvalue('aes_confirm_key'));

      $config->save();    
      $this->messenger()->addMessage($this->t('The configuration options have been saved.')); // Set the confirmation message      
    parent::submitForm($form, $form_state);
  }

    /**
   * {@inheritdoc}
   */
  // public function actions(array $form, FormStateInterface $form_state): array {
  //   $actions = parent::actions($form, $form_state);
  //   $actions['submit']['#value'] = $this->t('Save');
  //   return $actions;
  // }

}