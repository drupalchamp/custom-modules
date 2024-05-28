<?php

namespace Drupal\mulesoft_app\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Group;
use Drupal\user\UserInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to user apps operations for company.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("user_apps_operations")
 */
class UserAppOperations extends FieldPluginBase {

  /**
   * @{inheritdoc}
   */
  public function query() {

  }

  /**
   * Define the available options
   *
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $current_path = \Drupal::service('path.current')->getPath();
    $explode = explode("/", $current_path);
    $gid = $explode[3];
    $uid = 0;
    if ($values->_relationship_entities['gc__user_1'] instanceof UserInterface) {
      $uid = $values->_relationship_entities['gc__user_1']->id();
    }
    $group = Group::load($gid);
    if ($group) {
      $member = $group->getMember(\Drupal::currentUser());
      $member_roles = $member->getRoles();
      $roles_data = array_keys($member_roles);
    }
    $data = [
      'uid' => $uid,
      'gid' => $gid,
    ];
    $data['admin_user'] = FALSE;
    if (in_array("default-primary_contact", $roles_data) || in_array("default-administrator", $roles_data)) {
      $data['admin_user'] = TRUE;
    }
    $admin_members = $group->getMembers(['default-primary_contact']);
    foreach ($admin_members as $admin_member) {
      $admin_uid = $admin_member->getGroupContent()
        ->get('entity_id')->target_id;
      if ($admin_uid == $uid) {
        $data['admin_user'] = FALSE;
        break;
      }
    }
    $build = [
      '#theme' => "userappoperations",
      '#data' => $data,
      '#cache' => ['max-age' => 0],
    ];
    return $build;
  }

}
