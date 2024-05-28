<?php

namespace Drupal\mulesoft_app\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Group;
use Drupal\user\Entity\User;

/**
 * Class MulesoftEditMemberForm.
 */
class MulesoftEditMemberForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mulesoft_member_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $uid = NULL, $gid = NULL, $operation = NULL) {
    $submit = "Save";
    $user_load = User::load($uid);
    $first_name = $user_load->get("field_first_name")->value;
    $last_name = $user_load->get("field_last_name")->value;
    $name = "$first_name $last_name";
    $group = Group::load($gid);
    $roles_data = [];
    if ($group) {
      $label = $group->label();
      $member = $group->getMember($user_load);
      $member_roles = $member->getRoles();
      $form['roles'] = [
        '#type' => 'hidden',
        '#value' => $member_roles,
      ];
      $roles_data = array_keys($member_roles);
    }
    $id = "edit-member-form";
    $form['#prefix'] = '<div id="' . $id . '">';
    $form['operation_label'] = [
      '#type' => 'hidden',
      '#value' => "edit-warning",
    ];
    $form['operation_class'] = [
      '#type' => 'hidden',
      '#value' => "edit-app-warning",
    ];
    if ($operation == "admin") {
      $question_markup = "Are you sure you want to set <b>$name</b> as administrator?";
      $description_markup = "Administrator has the authority to invite new member, create/delete app, etc.";
      $submit = "Yes, set as administrator";
    }
    if ($operation == "developer") {
      $question_markup = "Are you sure you want to set <b>$name</b> as developer?";
      if (in_array("default-administrator", $roles_data)) {
        $description_markup = "This user will no longer be the administrator who has the authority to invite new member, create/delete app, etc.";
      }
      else {
        $description_markup = "Developer has the authority view app details.";
      }
      $submit = "Yes, set as developer";
    }
    if ($operation == "remove") {
      $id = "delete-member-form";
      $form['#prefix'] = '<div id="' . $id . '">';
      $question_markup = "You're going to remove <b>$name</b> from $label";
      $description_markup = "Removing this team member can't be undone.";
      $submit = "Remove member";
      $form['operation_label'] = [
        '#type' => 'hidden',
        '#value' => "delete-warning",
      ];
      $form['operation_class'] = [
        '#type' => 'hidden',
        '#value' => "delete-app-warning",
      ];
    }
    $form['edit_question_markup'] = [
      '#markup' => $this->t($question_markup),
    ];
    $form['edit_description_markup'] = [
      '#markup' => $this->t($description_markup),
    ];
    $form['#suffix'] = '</div>';
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];
    $form['div_id'] = [
      '#type' => 'hidden',
      '#value' => $id,
    ];
    $form['uid'] = [
      '#type' => 'hidden',
      '#value' => $uid,
    ];
    $form['email'] = [
      '#type' => 'hidden',
      '#value' => $user_load->getEmail(),
    ];
    $form['gid'] = [
      '#type' => 'hidden',
      '#value' => $gid,
    ];
    $form['operation'] = [
      '#type' => 'hidden',
      '#value' => $operation,
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
      '#value' => $this->t($submit),
    ];
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
    $values = $form_state->getValues();
    $companyID = $values['gid'];
    $id = $values['div_id'];
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#' . $id, $form));
    }
    else {
      $command = new RedirectCommand("/company/members/$companyID");
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
    $operation = $values['operation'];
    $member_roles = $values['roles'];
    $email = $values['email'];
    if ($operation != "remove") {
      $roles = [
        "admin" => "default-administrator",
        "developer" => "default-developer",
      ];
      if ($operation == "admin") {
        $error_message = "@email has already a administrator role.";
      }
      elseif ($operation == "developer") {
        $error_message = "@email has already a developer role.";
      }
      $roles_data = array_keys($member_roles);
      if (in_array($roles[$operation], $roles_data)) {
        $form_state->setErrorByName("uid", $this->t($error_message, ["@email" => $email]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $gid = $values['gid'];
    $uid = $values['uid'];
    $user_load = User::load($uid);
    $email = $user_load->getEmail();
    $group = Group::load($gid);
    $operation = $values['operation'];
    $membership = $group->getMember($user_load)->getGroupContent();
    if ($group) {
      if ($operation != "remove") {
        if ($operation == "admin") {
          $role = "default-administrator";
          $message = "Administrator role successfully added to @email";
        }
        elseif ($operation == "developer") {
          $role = "default-developer";
          $message = "Developer role successfully added to @email";
        }
        $membership->group_roles = $role;
        $membership->save();
        \Drupal::messenger()
          ->addStatus($this->t($message, ["@email" => $email]));
      }
      else {
        \Drupal::messenger()
          ->addStatus($this->t("@email successfully deleted.", ["@email" => $email]));
        $group->removeMember($user_load);
      }
    }
  }

}
