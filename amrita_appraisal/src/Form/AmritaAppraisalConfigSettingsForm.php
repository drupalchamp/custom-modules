<?php

declare(strict_types=1);

namespace Drupal\amrita_appraisal\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Amrita appraisal settings for this site.
 */
final class AmritaAppraisalConfigSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'amrita_appraisal_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['amrita_appraisal.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('amrita_appraisal.settings'); // Retrieve the configuration.

    $form['accordian_setting_1'] = array(
      '#type' => 'details',
      '#title' => $this->t('CALCULATION SETTINGS'),
      '#open' => TRUE, // Set to TRUE to keep it open by default.
    );

    $form['accordian_setting_1']['min_points'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum Points:'),
      '#description' => $this->t("Enter the minimum points required before a policy is deemed attractive."),
      '#default_value' => $config->get('min_points') ?? '14',
      '#required' => TRUE,
    ];

    $form['accordian_setting_1']['upper_irr'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Upper IRR:'),
      '#description' => $this->t("Enter a ratio, e.g. enter 0.18 for an 18% IRR."),
      '#default_value' => $config->get('upper_irr') ?? '0.2',
      '#required' => TRUE,
    ];

    $form['accordian_setting_1']['lower_irr'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Lower IRR:'),
      '#description' => $this->t("Enter a ratio, e.g. enter 0.13 for a 13% IRR."),
      '#default_value' => $config->get('lower_irr') ?? '0.15',
      '#required' => TRUE,
    ];

    $form['accordian_setting_1']['rpf_upper_irr'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recourse Premium Finance Upper IRR:'),
      '#description' => $this->t("Enter a ratio, e.g. enter 0.20 for an 20% IRR."),
      '#default_value' => $config->get('rpf_upper_irr') ?? '0.22',
      '#required' => TRUE,
    ];

    $form['accordian_setting_1']['rpf_lower_irr'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recourse Premium Finance Lower IRR:'),
      '#description' => $this->t("Enter a ratio, e.g. enter 0.15 for a 15% IRR."),
      '#default_value' => $config->get('rpf_lower_irr') ?? '0.17',
      '#required' => TRUE,
    ];

    $form['accordian_setting_1']['nrpf_upper_irr'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Non-recourse Premium Finance Upper IRR:'),
      '#description' => $this->t("Enter a ratio, e.g. enter 0.40 for an 40% IRR."),
      '#default_value' => $config->get('nrpf_upper_irr') ?? '0.4',
      '#required' => TRUE,
    ];

    $form['accordian_setting_1']['nrpf_lower_irr'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Non-recourse Premium Finance Lower IRR:'),
      '#description' => $this->t("Enter a ratio, e.g. enter 0.30 for a 30% IRR."),
      '#default_value' => $config->get('nrpf_lower_irr') ?? '0.33',
      '#required' => TRUE,
    ];

    $form['accordian_setting_2'] = array(
      '#type' => 'details',
      '#title' => $this->t('CONVERTIBLE TERM APPRAISALS'),
      '#open' => TRUE, // Set to TRUE to keep it open by default.
    );

    $form['accordian_setting_2']['max_le_month'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum LE (months):'),
      '#description' => $this->t("Enter the maximum life expectancy threshold for convertible term policies."),
      '#default_value' => $config->get('max_le_month') ?? '180',
      '#required' => TRUE,
    ];

    $form['accordian_setting_2']['max_age'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum Age:'),
      '#description' => $this->t("Enter the maximum insured age threshold for convertible term policies."),
      '#default_value' => $config->get('max_age') ?? '75',
      '#required' => TRUE,
    ];

    $form['accordian_setting_2']['approved_carriers'] = [
      '#required' => TRUE,
      '#type' => 'textarea',
      '#title' => $this->t('Approved Carriers:'),
      '#description' => $this->t("List the approved carriers, one per line, for convertible term transactions."),
      '#default_value' => $config->get('approved_carriers') ?? "American General\nAmerican National\nAVIVA\nAXA\nAXA Financial\nAXA Equitable\nBanner Life\nGenworth\nING\nJackson National\nJohn Hancock\nLincoln Benefit\nLincoln National\nMass Mutual\nMetLife Investors\nMetLife\nMidland National\nNew York Life\nNorth American Company for Health and Life\nPacific Life\nPrudential\nTransamerica\nUnited of Omaha\nUS Life\nWest Coast Life\nWilliam Penn",      
    ];

    $form['accordian_setting_3'] = array(
      '#type' => 'details',
      '#title' => $this->t('GENERAL SETTINGS'),
      '#open' => TRUE, // Set to TRUE to keep it open by default.
    ); 

    $form['accordian_setting_3']['log_appraisals'] = [
      '#type' => 'radios',
      '#options' => [
        '0' => $this->t('No'),
        '1' => $this->t('Yes'),          
      ], 
      '#title' => $this->t('Log Appraisals:'),
      '#description' => $this->t("Should appraisal requests be logged to the database?"),
      '#default_value' => $config->get('log_appraisals') ?? '1',
      '#required' => TRUE,
    ];

    $form['accordian_setting_3']['commission_refund'] = [
      '#type' => 'radios',
      '#options' => [
        '0' => $this->t('No'),
        '1' => $this->t('Yes'),          
      ], 
      '#title' => $this->t('Commission Refund:'),
      '#description' => $this->t("Show refund information in appraisal results."),
      '#default_value' => $config->get('commission_refund') ?? '0',
      '#required' => TRUE,
    ];

    // Add a reset button to reset the configuration to default values
    $form['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset to Defaults'),
      '#weight' => 1001,
      '#submit' => ['::resetForm'],
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
      $config = $this->config('amrita_appraisal.settings'); // Retrieve the configuration.
      // Set the submitted configuration setting.
      $config->set("min_points", $form_state->getvalue('min_points'));
      $config->set("upper_irr", $form_state->getvalue('upper_irr'));
      $config->set("lower_irr", $form_state->getvalue('lower_irr'));
      $config->set("rpf_upper_irr", $form_state->getvalue('rpf_upper_irr'));
      $config->set("rpf_lower_irr", $form_state->getvalue('rpf_lower_irr'));
      $config->set("nrpf_upper_irr", $form_state->getvalue('nrpf_upper_irr'));
      $config->set("nrpf_lower_irr", $form_state->getvalue('nrpf_lower_irr'));
      $config->set("max_le_month", $form_state->getvalue('max_le_month'));
      $config->set("max_age", $form_state->getvalue('max_age'));
      $config->set("approved_carriers", $form_state->getvalue('approved_carriers'));
      $config->set("log_appraisals", $form_state->getvalue('log_appraisals'));
      $config->set("commission_refund", $form_state->getvalue('commission_refund'));

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
      'min_points' => '14', 
      'upper_irr' => '0.2',
      'lower_irr' => '0.15',
      'rpf_upper_irr' => '0.22',
      'rpf_lower_irr' => '0.17',
      'nrpf_upper_irr' => '0.4',
      'nrpf_lower_irr' => '0.33',
      'max_le_month' => '180',
      'max_age' => '75',
      'approved_carriers' => "American General\nAmerican National\nAVIVA\nAXA\nAXA Financial\nAXA Equitable\nBanner Life\nGenworth\nING\nJackson National\nJohn Hancock\nLincoln Benefit\nLincoln National\nMass Mutual\nMetLife Investors\nMetLife\nMidland National\nNew York Life\nNorth American Company for Health and Life\nPacific Life\nPrudential\nTransamerica\nUnited of Omaha\nUS Life\nWest Coast Life\nWilliam Penn",      
      'log_appraisals' => '1',
      'commission_refund' => '0',
    
    ];

    // Reset the configuration to the default values
    $config = $this->config('amrita_appraisal.settings');
    $config->setData($default_values);
    $config->save();
    
    $this->messenger()->addMessage($this->t('The configuration options have been reset to their default values.')); // Set the confirmation message

    // Redirect to the configuration form page to show the changes
    $form_state->setRedirect('amrita_appraisal.settings');
  }

}
