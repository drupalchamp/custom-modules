<?php

namespace Drupal\mulesoft_App\Access;

use Drupal\apiinfo\Entity\ApiinfoEntity;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\group\Entity\Group;

/**
 * Checks access for App Edit/Delete pages.
 */
class AppAccessCheck implements AccessInterface {

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, $app_id) {
    $app_load = ApiinfoEntity::load($app_id);
    if ($app_load) {
      if ($account->id() == $app_load->getOwnerId()) {
        return AccessResult::allowed();
      }
      else {
        $user_groups = $app_load->get("field_user_group")->getValue();
        if ($user_groups) {
          foreach ($user_groups as $user_group) {
            $gid = $user_group['target_id'];
            $group_load = Group::load($gid);
            if ($group_load) {
              if ($group_load->getMember($account)) {
                return AccessResult::allowed();
              }
            }
          }
        }
      }
    }
    return AccessResult::forbidden();
  }

}
