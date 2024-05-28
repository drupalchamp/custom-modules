<?php

namespace Drupal\mulesoft_app\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\mulesoft_app\Controller\MyappuserpageController;
use Drupal\user\UserInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to user apps for company.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("user_apps_count")
 */
class UserAppCount extends FieldPluginBase {

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
    // Get current path
    $current_path = \Drupal::service('path.current')->getPath();
    $explode = explode("/", $current_path);
    $gid = $explode[3];
    $uid = 0;
    if ($values->_relationship_entities['gc__user_1'] instanceof UserInterface) {
      $uid = $values->_relationship_entities['gc__user_1']->id();
    }
    $myapps = new MyappuserpageController();
    $count_apps = $myapps->getUserApps($uid, $gid, TRUE);
    return $count_apps;
  }

}
