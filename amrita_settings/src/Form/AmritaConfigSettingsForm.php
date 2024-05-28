<?php

declare(strict_types=1);

namespace Drupal\amrita_settings\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Amrita settings for this site.
 */
final class AmritaConfigSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'amrita_settings_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['amrita_settings.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('amrita_settings.settings'); // Retrieve the configuration.

    $form['query_financial_advisers'] = [
      '#type' => 'radios',
      '#options' => [
        '0' => $this->t('No'),
        '1' => $this->t('Yes'),          
      ], 
      '#title' => $this->t('Query Financial Advisers:'),
      '#description' => $this->t("Query financial advisers for commission on policy submit."),
      '#default_value' => $config->get('query_financial_advisers') ?? '0',
      '#required' => TRUE,
    ];

    $form['filter_new_cases'] = [
      '#type' => 'radios',
      '#options' => [
        '0' => $this->t('No'),
        '1' => $this->t('Yes'),          
      ], 
      '#title' => $this->t('Filter New Cases:'),
      '#description' => $this->t("Should non-attractive case applications be rejected?"),
      '#default_value' => $config->get('filter_new_cases') ?? '1',
      '#required' => TRUE,
    ];

    // Add a reset button to reset the configuration to default values
    $form['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset to Defaults'),
      '#weight' => 1001,
      '#submit' => ['::resetForm'],
    ];

    return parent::buildForm($form, $form_state);
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
  public function submitForm(array &$form, FormStateInterface $form_state): void {
      $config = $this->config('amrita_settings.settings'); // Retrieve the configuration.
      // Set the submitted configuration setting.
      $config->set("query_financial_advisers", $form_state->getvalue('query_financial_advisers'));
      $config->set("filter_new_cases", $form_state->getvalue('filter_new_cases'));
      $config->save();
      
    $this->messenger()->addMessage($this->t('The configuration options have been saved.')); // Set the confirmation message
    parent::submitForm($form, $form_state);
  }

  /**
   * Custom submission handler for the reset button.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    // Define the default values
    $default_values = [
      'query_financial_advisers' => '0',
      'filter_new_cases' => '1',
    ];

    // Reset the configuration to the default values
    $config = $this->config('amrita_settings.settings');
    $config->setData($default_values);
    $config->save();
    
    $this->messenger()->addMessage($this->t('The configuration options have been reset to their default values.')); // Set the confirmation message

    // Redirect to the configuration form page to show the changes
    $form_state->setRedirect('amrita_settings.settings');
  }

}
