<?php

namespace Drupal\mulesoft_app\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Group;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Implements the AddMember form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class AddMember extends FormBase {


  /**
   * Getter method for Form ID.
   *
   * The form ID is used in implementations of hook_form_alter() to allow other
   * modules to alter the render array built by this form controller. It must be
   * unique site wide. It normally starts with the providing module's name.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'addmember_form';
  }

  /**
   * @param array $form
   *   Default form array structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object containing current form state.
   *
   * @return array
   *   The render array defining the elements of the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $destination = \Drupal::request()->get('redirect');
    $default_value = '';
    if ($destination) {
      $redirect_explode = explode("/", $destination);
      $form['redirect'] = [
        '#type' => 'hidden',
        '#value' => $redirect_explode[6],
      ];
      $default_value = $redirect_explode[6];
    }
    $gids = \Drupal::entityQuery('group')
      ->condition('type', 'default')
      ->execute();
    $group_data = [];
    if ($gids) {
      foreach ($gids as $gid) {
        $groupLoad = Group::load($gid);
        if ($groupLoad) {
          $group_data[$gid] = $groupLoad->label();
        }
      }
    }
    $form['companyname'] = [
      '#type' => 'select',
      '#empty_option' => $this->t("Select Company"),
      '#title' => $this->t('Company'),
      '#options' => $group_data,
      '#default_value' => $default_value,
      '#required' => TRUE,
    ];
    $form['user_email'] = [
      '#type' => 'email',
      '#title' => $this->t('User Email'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Member'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
    ];
    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $clickedElement = $form_state->getTriggeringElement()['#parents'][0];
    if ($clickedElement == "submit") {
      $values = $form_state->getValues();
      $userEmail = $values['user_email'];
      if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
        $form_state->setErrorByName("user_email", $this->t("@email is not a valid email address.", ["@email" => $userEmail]));
      }
      else {
        $user_load = user_load_by_mail($userEmail);
        if (!$user_load) {
          $form_state->setErrorByName("user_email", $this->t("@email is not a available.", ["@email" => $userEmail]));
        }
        else {
          $userLoad = User::load($user_load->id());
          $companyLoad = Group::load($values['companyname']);
          if ($userLoad) {
            if (!$userLoad->hasRole("corporate_company")) {
              $userLoad->addRole("corporate_company");
              $userLoad->save();
            }
          }
          $getMember = $companyLoad->getMember($userLoad);
          if ($getMember) {
            $form_state->setErrorByName("user_email", $this->t("@email is already a member in selected company.", ["@email" => $userEmail]));
          }
        }
      }
    }
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $clickedElement = $form_state->getTriggeringElement()['#parents'][0];
    $values = $form_state->getValues();
    $userEmail = $values['user_email'];
    $redirect = isset($values['redirect']) ? $values['redirect'] : '';
    if ($clickedElement == "submit") {
      $user_load = user_load_by_mail($userEmail);
      $userLoad = User::load($user_load->id());
      $companyLoad = Group::load($values['companyname']);
      $companyLoad->addMember($userLoad);
      \Drupal::messenger()
        ->addMessage(t('@email successfully added as a member in selected company.', ['@email' => $userEmail]));
    }
    if ($redirect) {
      $url = Url::fromUserInput("/company/list/members/$redirect");
      $form_state->setRedirectUrl($url);
    }
    else {
      $form_state->setRedirect("mulesoft_app.company_listing");
    }
  }

}
