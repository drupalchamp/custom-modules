<?php

namespace Drupal\mulesoft_App\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Checks access for My App and Add App forms.
 */
class UserApproveAccessCheck implements AccessInterface {

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    if ($account->isAnonymous()) {
      return AccessResult::forbidden();
    }
    else {
      $uid = $account->id();
      $userLoad = User::load($uid);
      if ($userLoad) {
        $company = $userLoad->get("field_company")->target_id;
        $isAdmin = $userLoad->hasRole("administrator");
        $isReviewer = $userLoad->hasRole("reviewer");
        if (!$company && !$isAdmin && !$isReviewer) {
          return AccessResult::forbidden();
        }
      }
    }
    return AccessResult::allowed();
  }

}
