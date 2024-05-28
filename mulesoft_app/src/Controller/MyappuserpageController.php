<?php

namespace Drupal\mulesoft_app\Controller;

use Drupal\apiinfo\Entity\ApiinfoEntity;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


/**
 * Provides route responses for the DrupalBook module.
 */
class MyappuserpageController {

  /**
   * Returns a myapp user page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function myapp() {
    \Drupal::service('page_cache_kill_switch')->trigger();
    $user = \Drupal::currentUser();
    if (empty($user->id())) {
      return [];
    }
    $typeofapigateway = \Drupal::config('mulesoft_app.apigatewaytype')
      ->get('typeofapigateway');
    $theme = 'myapplist';
    $apidetails = [];
    switch ($typeofapigateway) {
      case 'mulesoftapigateway':
        $query = \Drupal::entityQuery('apiinfo_entity')
          ->condition('field_app_owner', $user->id())
          ->condition('type', 'mulesoft_api_gateway_keys');
        $or = $query->orConditionGroup();
        $or->condition('field_unpublish', 0);
        $or->notExists('field_unpublish');
        $query->condition($or);
        $query->accessCheck(FALSE);
        $result = $query->execute();
        $appentity = \Drupal::entityTypeManager()
          ->getStorage('apiinfo_entity')
          ->loadMultiple($result);
        $theme = 'myapplistmulesoftapigateway';
        $mulesoft_connector = \Drupal::service('mulesoft_app.sdk_connector');
        $access_token = $mulesoft_connector->getaccesstoken();
        $client = $mulesoft_connector->gethttpclient();
        $mulesoft_config = \Drupal::config('mulesoft_app.auth');
        $baseurl = $mulesoft_config->get('baseurl');
        $org_id = $mulesoft_config->get('orgid');
        $host = 'https://' . $baseurl . '/apiplatform/repository/v2/organizations/' . $org_id . '/applications';
        try {
          $response = $client->get('https://' . $baseurl . '/apimanager/api/v1/organizations/' . $org_id . '/groups', [
            'headers' => [
              'Content-Type' => 'application/json',
              'Authorization' => 'Bearer ' . $access_token,
            ],
          ]);
          $code = $response->getStatusCode();
          if ($code == 200) {
            if ($response->getBody()) {
              $response_body = json_decode($response->getBody());
              if ($response_body) {
                foreach ($response_body->groups as $key => $each_group) {
                  $instance_id = $each_group->versions[0]->instances[0]->id;
                  if ($instance_id) {
                    $query = \Drupal::entityQuery('node');
                    $query->condition('status', 1);
                    $query->condition('type', 'mulesoft_api');
                    $query->condition('field_mulesoft_api_id', $instance_id);
                    $api_node_id_array = $query->execute();
                    $node_id = array_pop($api_node_id_array);
                    $apidetails[$node_id] = $each_group->name;
                  }
                }
              }
            }
          }
        } catch (RequestException $e) {
          if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception);
            \Drupal::logger('mulesoft_app')->error($exception);
            \Drupal::messenger()->addMessage($exception, 'error');
          }
        }
        break;
      default :
        $result = \Drupal::entityQuery('node')
          ->condition('type', 'my_app')
          ->condition('field_user', $user->id())
          ->accessCheck(FALSE)
          ->execute();
        $appentity = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->loadMultiple($result);
    }
    $element = [
      '#theme' => $theme,
      '#appentity' => $appentity,
      '#apidetails' => $apidetails,
    ];
    return $element;
  }

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function myappaddapp() {
    $element = [];
    return $element;
  }

  /**
   * Returns a controller to close modal popup.
   */
  public function closeModalForm() {
    $command = new CloseModalDialogCommand();
    $response = new AjaxResponse();
    $response->addCommand($command);
    return $response;
  }

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function appclient() {
    $element = [];
    return $element;
  }

  /**
   * Returns a company listing page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function companyList() {
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
    $element = [
      '#theme' => "companylisting",
      '#data' => $group_data,
      '#cache' => ['max-age' => 0],
    ];
    return $element;
  }

  /**
   * Returns a company listing page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function listUsercompanies() {
    $group_data = [];
    $gids = [];
    $grp_membership_service = \Drupal::service('group.membership_loader');
    $user = User::load(\Drupal::currentUser()->id());
    $grps = $grp_membership_service->loadByUser($user);
    foreach ($grps as $grp) {
      $gids[] = $grp->getGroup();
    }
    if ($gids) {
      foreach ($gids as $gid) {
        $group_data[$gid->id()] = $gid->label();
      }
    }
    $element = [
      '#theme' => "mycompanylisting",
      '#data' => $group_data,
      '#cache' => ['max-age' => 0],
    ];
    return $element;
  }

  /**
   * Returns a user apps created in company.
   */
  public function getUserApps($uid = NULL, $gid = NULL, $count = FALSE) {
    $query = \Drupal::entityQuery('apiinfo_entity')
      ->condition('field_app_owner', $uid)
      ->condition('field_user_group', [$gid], "IN")
      ->condition('type', 'mulesoft_api_gateway_keys');
    $or = $query->orConditionGroup();
    $or->condition('field_unpublish', 0);
    $or->notExists('field_unpublish');
    $query->condition($or);
    $query->accessCheck(FALSE);
    if ($count) {
      $query->count();
    }
    $result = $query->execute();
    return $result;
  }

  /**
   * Returns a user apps operations.
   */
  public function userAppOperations($gid = NULL, $uid = NULL, $operation = NULL) {
    $build = [
      '#markup' => "Requested page not found",
    ];
    $user_load = User::load($uid);
    if ($user_load) {
      $first_name = $user_load->get("field_first_name")->value;
      $last_name = $user_load->get("field_last_name")->value;
      $data = [
        'name' => "$first_name $last_name",
        'email' => $user_load->getEmail(),
        'gid' => $gid,
      ];
    }
    if ($operation == "apps") {
      $apps = [];
      $apps_data = $this->getUserApps($uid, $gid);
      if ($apps_data) {
        foreach ($apps_data as $appID) {
          $app_load = ApiinfoEntity::load($appID);
          if ($app_load) {
            $apps[] = [
              'label' => $app_load->label(),
              'id' => $appID,
            ];
          }
        }
      }
      $build = [
        '#theme' => "userapps",
        '#apps' => $apps,
        '#data' => $data,
        '#cache' => ['max-age' => 0],
      ];
    }
    elseif ($operation == "admin" || $operation == "developer" || $operation == "remove") {
      $build = [];
      $form_class = '\Drupal\mulesoft_app\Form\MulesoftEditMemberForm';
      $build['form'] = \Drupal::formBuilder()
        ->getForm($form_class, $uid, $gid, $operation);
    }
    return $build;
  }

  /**
   * @param $app_id
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * Get Details of APP to view.
   */
  public function details_mulesoft_app($app_id) {
    $item = \Drupal::entityTypeManager()
      ->getStorage('apiinfo_entity')
      ->load($app_id);
    $theme = 'userdetailsapp';
    $mulesoft_apps_data = [];
    if ($item) {
      $gid = \Drupal::request()->query->get('company');
      $show_api_keys = TRUE;
      if ($gid) {
        $company_load = Group::load($gid);
        if ($company_load) {
          $member = $company_load->getMember(\Drupal::currentUser());
          $member_roles = array_keys($member->getRoles());
          if (in_array("default-member", $member_roles) && count($member_roles) == 1) {
            $show_api_keys = FALSE;
          }
        }
      }
      $unpublish = $item->get("field_unpublish")->value;
      if ($unpublish) {
        throw new AccessDeniedHttpException();
      }
      $mulesoft_apps = $item->get("field_mulesoft_apis")->getValue();
      foreach ($mulesoft_apps as $mulesoft_app) {
        $mulesoft_app_load = Node::load($mulesoft_app['target_id']);
        if ($mulesoft_app_load) {
          $alias = \Drupal::service('path.alias_manager')
            ->getAliasByPath('/node/' . $mulesoft_app['target_id']);
          $api_icon_url = '';
          if ($mulesoft_app_load->hasField("field_api_icon")) {
            $api_icon_id = $mulesoft_app_load->get("field_api_icon")->target_id;
            if ($api_icon_id) {
              $api_icon_url = file_create_url($mulesoft_app_load->field_api_icon->entity->getFileUri());;
            }
          }
          $mulesoft_apps_data[$mulesoft_app['target_id']] = [
            'title' => $mulesoft_app_load->getTitle(),
            'overview_url' => $alias,
            'icon_url' => $api_icon_url,
          ];
        }
      }
      $element = [
        '#cache' => ['max-age' => 0],
        '#theme' => $theme,
        '#item' => [
          'client_id' => $item->get('field_client_id')->value,
          'client_secret' => $item->get('field_client_secret')->value,
          'title' => $item->label(),
          'status' => $item->get("status")->value,
          'app_id' => $item->id(),
          'appId' => $item->get("field_app_id")->value,
          'apiAccess' => $mulesoft_apps_data,
          'show_api_keys' => $show_api_keys,
        ],
      ];
    }
    else {
      $element['data'] = [
        '#theme' => $theme,
      ];
    }
    return $element;
  }

}
