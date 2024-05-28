<?php

declare(strict_types=1);

namespace Drupal\amrita_index\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Amrita index settings for this site.
 */
final class AmritaIndexCinfigSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'amrita_index_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['amrita_index.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('amrita_index.settings'); // Retrieve the configuration.

    $form['enable_survey'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Survey'),
      '#description' => $this->t("Enables survey open/close dates and automatic invitations."),
      '#default_value' => $config->get('enable_survey') ?? TRUE,
    ];

    $form['survey_odc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Survey Open Date Code:'),
      '#description' => $this->t("Relative to the survey's closing date. For example, the code: '-14 days' equates to two weeks before the survey closes."),
      '#default_value' => $config->get('survey_odc') ?? '-12 days +13 hours',
    ];

    $form['survey_rdc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Survey Reminder Date Code:'),
      '#description' => $this->t("Relative to the survey's closing date. For example, the code: '-7 days' equates to one week before the survey closes."),
      '#default_value' => $config->get('survey_rdc') ?? '-6 days +9 hours',
    ];

    $form['survey_cdc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Survey Close Date Code:'),
      '#description' => $this->t("For example, the code: '+0 week tue' equates to the 1st Tuesday of every month."),
      '#default_value' => $config->get('survey_cdc') ?? '+0 week tue',
    ];

    $form['provider_email_address'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Provider E-mail Addresses:'),
      '#description' => $this->t("List the e-mail addresses, one per line, of the providers to invite to take the index survey."),
      '#default_value' => $config->get('provider_email_address') ?? "rrodas@viamagna.com\nTOtt@lslcorp.com\nbgabriel@vfgus.com\nbhotzman@milestonesettlements.com\nbobp@abacussettlements.com\ncregler@legacybenefits.com\ndale@southcoastsettlements.com\necole@lumpsum.com\neisler@progressivecapital.net\nerez@ilifesolutionsllc.com\njberry@ilcompanies.com\njcobert@proverian.com\njj@allfinancialgroupllc.com\njlang@lifeequity.net\njpipon@thelifeline.com\njruggiero@ilcompanies.com\nlbolich@milestonesettlements.com\nmgrullon@viamagna.com\nwgrados@viamagna.com\nmichaelb@neumainc.com\ndmarantz@gwglife.com\nparcsidecapital@yahoo.com\npkoons@maplelf.com\nroconnor@progressivecapital.net\nsloy@berkshiresettlements.com\nsturner@habershamfunding.com\ntodd@ogpltd.com\nz.beca@secondarylifecapital.com\nBrad.Hernandez@caldwell-ls.com\ndle@berlinatlantic.com\nweiss@progressivecapital.net\nkklein@fairmarketlife.com\nl.sears@j-chapman.com\nsettle@eagil.com\ndjg@qcapital.com\nterry@montagegroup.com\npeter.Mazonas@lifesettlementfinancial.com\ngrising@fairmarketlife.com\njmoulton@imprl.com\nlucask@abacussettlements.com\nDKyle@coventry.com\nderek@montagegroup.com\nphenry@viamagna.com\nchristian@amritafinancial.com\nkevin@amritafinancial.com\nssabes@gwglife.com\nmspence@gwglife.com\ndpatashnik@legacybenefits.com\nkmcnamara@lslcorp.com\ncevulich@hotmail.com\ncevulich@gmail.com\npsiegert@insurancestudies.org\ninfo@absolutels.com\neu@allfinancialgroupllc.com\ntlevy@abacussettlements.com\nTHagan@coventry.com\nEJohnson@lifepolicytraders.com\nrtonry@lifepolicytraders.com\njdallas@berkshiresettlements.com\nj.chapman@j-chapman.com\ncases@greatpotomac.com\nmhomier@lifecapitalcorporation.com\ndcraig@lifecapitalcorporation.com\nsbutcher@abacussettlements.com\nProviderpaul81@gmail.com\nhsmaan@synapseindia.email",      
    ];

    $form['accordian_sett_1'] = array(
      '#type' => 'details',
      '#title' => $this->t('INVITATION E-MAIL'),
      '#description' => $this->t("Customize the invitation e-mail message sent to providers for the index survey."),
      '#open' => FALSE, // Set to TRUE to keep it open by default.
    ); 

    $form['accordian_sett_1']['ai1_subject'] = [      
      '#type' => 'textfield',
      '#title' => $this->t('Subject:'),      
      '#default_value' => $config->get('ai1_subject') ?? 'Life Settlement Provider Survey',
    ];

    $form['accordian_sett_1']['ai1_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body:'),
      '#default_value' => $config->get('ai1_body') ?? "Hello,\n\nAs part of this month's Amrita Life Settlement Index, we are conducting an 8 question survey. Your response would be greatly appreciated. All responses are 100% anonymous. This month's survey ends on !close-date, so please be sure to complete it by then.\n\nHere is a link to the survey:\n\n!survey-url\n\nRemember all participating life settlement providers receive additional market data not available to the public. Thank you very much for your participation! Please contact us at (888) 539-8885 x103 or Christian@amritafinancial.com with any questions.\n\nIf you no longer wish to receive surveys in the future, please click the following link: !optout-url",      
    ];

    $form['accordian_sett_2'] = array(
      '#type' => 'details',
      '#title' => $this->t('REMINDER E-MAIL'),
      '#description' => $this->t("Customize the reminder e-mail message sent to providers for the index survey."),
      '#open' => FALSE, // Set to TRUE to keep it open by default.
    ); 

    $form['accordian_sett_2']['ai2_subject'] = [      
      '#type' => 'textfield',
      '#title' => $this->t('Subject:'),      
      '#default_value' => $config->get('ai2_subject') ?? 'Life Settlement Provider Survey Reminder',
    ];

    $form['accordian_sett_2']['ai2_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body:'),
      '#default_value' => $config->get('ai2_body') ?? "Hello,\n\nThis is a friendly reminder that we haven't received a life settlement provider survey from you this month. It takes less than 60 seconds and all responses are 100% anonymous. This month's survey ends on !close-date.\n\nHere is a link to the survey:\n\n!survey-url\n\nRemember, all participating providers receive valuable market intelligence not available to the public. Thanks again for your participation! Please contact us at (888) 539-8885 x103 or Christian@amritafinancial.com with any questions.\n\nIf you no longer wish to receive surveys in the future, please click the following link: !optout-url",      
    ];

    $form['accordian_sett_3'] = array(
      '#type' => 'details',
      '#title' => $this->t('RESULT E-MAIL'),
      '#description' => $this->t("Customize the result e-mail message sent to survey respondents."),
      '#open' => FALSE, // Set to TRUE to keep it open by default.
    ); 

    $form['accordian_sett_3']['ai3_subject'] = [      
      '#type' => 'textfield',
      '#title' => $this->t('Subject:'),      
      '#default_value' => $config->get('ai3_subject') ?? 'Amrita Life Settlement Index - Detailed Results',
    ];

    $form['accordian_sett_3']['ai3_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body:'),
      '#default_value' => $config->get('ai3_body') ?? "This is an automated email system, please do not reply.\n\nAs a thank you for your recent participation in the Amrita Life Settlement Provider Survey, we are providing a report with the averaged responses of all respondents. We hope this valuable market data proves helpful in your endeavours. Thanks again and we look forward to your input on next month's survey.\n\nHere is a link to the !date results:\n\n!result-url\n\nBest regards,\n\nThe Amrita Financial Team\nhttp://www.AmritaFinancial.com",      
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('amrita_index.settings'); // Retrieve the configuration.
    // Set the submitted configuration setting.
    $config->set("enable_survey", $form_state->getvalue('enable_survey'));
    $config->set("survey_odc", $form_state->getvalue('survey_odc'));
    $config->set("survey_rdc", $form_state->getvalue('survey_rdc'));
    $config->set("survey_cdc", $form_state->getvalue('survey_cdc'));
    $config->set("provider_email_address", $form_state->getvalue('provider_email_address'));
    $config->set("ai1_subject", $form_state->getvalue('ai1_subject'));
    $config->set("ai1_body", $form_state->getvalue('ai1_body'));
    $config->set("ai2_subject", $form_state->getvalue('ai2_subject'));
    $config->set("ai2_body", $form_state->getvalue('ai2_body'));
    $config->set("ai3_subject", $form_state->getvalue('ai3_subject'));
    $config->set("ai3_body", $form_state->getvalue('ai3_body'));

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
      'enable_survey' => TRUE,
      'survey_odc' => '-12 days +13 hours',
      'survey_rdc' => '-6 days +9 hours',
      'survey_cdc' => '+0 week tue',
      'provider_email_address' => "rrodas@viamagna.com\nTOtt@lslcorp.com\nbgabriel@vfgus.com\nbhotzman@milestonesettlements.com\nbobp@abacussettlements.com\ncregler@legacybenefits.com\ndale@southcoastsettlements.com\necole@lumpsum.com\neisler@progressivecapital.net\nerez@ilifesolutionsllc.com\njberry@ilcompanies.com\njcobert@proverian.com\njj@allfinancialgroupllc.com\njlang@lifeequity.net\njpipon@thelifeline.com\njruggiero@ilcompanies.com\nlbolich@milestonesettlements.com\nmgrullon@viamagna.com\nwgrados@viamagna.com\nmichaelb@neumainc.com\ndmarantz@gwglife.com\nparcsidecapital@yahoo.com\npkoons@maplelf.com\nroconnor@progressivecapital.net\nsloy@berkshiresettlements.com\nsturner@habershamfunding.com\ntodd@ogpltd.com\nz.beca@secondarylifecapital.com\nBrad.Hernandez@caldwell-ls.com\ndle@berlinatlantic.com\nweiss@progressivecapital.net\nkklein@fairmarketlife.com\nl.sears@j-chapman.com\nsettle@eagil.com\ndjg@qcapital.com\nterry@montagegroup.com\npeter.Mazonas@lifesettlementfinancial.com\ngrising@fairmarketlife.com\njmoulton@imprl.com\nlucask@abacussettlements.com\nDKyle@coventry.com\nderek@montagegroup.com\nphenry@viamagna.com\nchristian@amritafinancial.com\nkevin@amritafinancial.com\nssabes@gwglife.com\nmspence@gwglife.com\ndpatashnik@legacybenefits.com\nkmcnamara@lslcorp.com\ncevulich@hotmail.com\ncevulich@gmail.com\npsiegert@insurancestudies.org\ninfo@absolutels.com\neu@allfinancialgroupllc.com\ntlevy@abacussettlements.com\nTHagan@coventry.com\nEJohnson@lifepolicytraders.com\nrtonry@lifepolicytraders.com\njdallas@berkshiresettlements.com\nj.chapman@j-chapman.com\ncases@greatpotomac.com\nmhomier@lifecapitalcorporation.com\ndcraig@lifecapitalcorporation.com\nsbutcher@abacussettlements.com\nProviderpaul81@gmail.com\nhsmaan@synapseindia.email",
      'ai1_subject' => "Life Settlement Provider Survey",
      'ai1_body' => "Hello,\n\nAs part of this month's Amrita Life Settlement Index, we are conducting an 8 question survey. Your response would be greatly appreciated. All responses are 100% anonymous. This month's survey ends on !close-date, so please be sure to complete it by then.\n\nHere is a link to the survey:\n\n!survey-url\n\nRemember all participating life settlement providers receive additional market data not available to the public. Thank you very much for your participation! Please contact us at (888) 539-8885 x103 or Christian@amritafinancial.com with any questions.\n\nIf you no longer wish to receive surveys in the future, please click the following link: !optout-url",
      'ai2_subject' => 'Life Settlement Provider Survey Reminder',
      'ai2_body' => "Hello,\n\nThis is a friendly reminder that we haven't received a life settlement provider survey from you this month. It takes less than 60 seconds and all responses are 100% anonymous. This month's survey ends on !close-date.\n\nHere is a link to the survey:\n\n!survey-url\n\nRemember, all participating providers receive valuable market intelligence not available to the public. Thanks again for your participation! Please contact us at (888) 539-8885 x103 or Christian@amritafinancial.com with any questions.\n\nIf you no longer wish to receive surveys in the future, please click the following link: !optout-url",
      'ai3_subject' => "Amrita Life Settlement Index - Detailed Results",      
      'ai3_body' => "This is an automated email system, please do not reply.\n\nAs a thank you for your recent participation in the Amrita Life Settlement Provider Survey, we are providing a report with the averaged responses of all respondents. We hope this valuable market data proves helpful in your endeavours. Thanks again and we look forward to your input on next month's survey.\n\nHere is a link to the !date results:\n\n!result-url\n\nBest regards,\n\nThe Amrita Financial Team\nhttp://www.AmritaFinancial.com",
    ];

    // Reset the configuration to the default values
    $config = $this->config('amrita_index.settings');
    $config->setData($default_values);
    $config->save();
    
    $this->messenger()->addMessage($this->t('The configuration options have been reset to their default values.')); // Set the confirmation message

    // Redirect to the configuration form page to show the changes
    $form_state->setRedirect('amrita_index.settings');
  }

}
