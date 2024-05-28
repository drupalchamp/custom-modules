<?php

namespace Drupal\mulesoft_app\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class DeleteMulesoftAppController.
 */
class DetailsMulesoftAppController extends ControllerBase {

  public function details_mulesoft_app($app_id) {
    $item = \Drupal::entityTypeManager()
      ->getStorage('apiinfo_entity')
      ->load($app_id);
    $theme = 'detailsapp';
    $mulesoft_apps_data = [];
    if ($item) {
      $unpublish = $item->get("field_unpublish")->value;
      if ($unpublish) {
        throw new AccessDeniedHttpException();
      }
      $mulesoft_apps = $item->get("field_mulesoft_apis")->getValue();
      foreach ($mulesoft_apps as $mulesoft_app) {
        $mulesoft_app_load = Node::load($mulesoft_app['target_id']);
        if ($mulesoft_app_load) {
          $link = $mulesoft_app_load->get('field_api_overview_link')->uri;
          $overview_link = '#';
          if ($link) {
            $overview_link = str_replace("internal:", "", $link);
            $overview_link = str_replace("entity:", "/", $overview_link);
            if (strpos($overview_link, 'node/') !== FALSE) {
              $overview_link = \Drupal::service('path_alias.manager')
                ->getAliasByPath($overview_link);
            }
          }
          $api_icon_url = '';
          if ($mulesoft_app_load->hasField("field_api_icon")) {
            $api_icon_id = $mulesoft_app_load->get("field_api_icon")->target_id;
            if ($api_icon_id) {
              $api_icon_url = file_create_url($mulesoft_app_load->field_api_icon->entity->getFileUri());;
            }
          }
          $mulesoft_apps_data[$mulesoft_app['target_id']] = [
            'title' => $mulesoft_app_load->getTitle(),
            'overview_url' => $overview_link,
            'icon_url' => $api_icon_url,
          ];
        }
      }
      $element = [
        '#theme' => $theme,
        '#item' => [
          'client_id' => $item->get('field_client_id')->value,
          'client_secret' => $item->get('field_client_secret')->value,
          'title' => $item->label(),
          'status' => $item->get("status")->value,
          'app_id' => $item->id(),
          'appId' => $item->get("field_app_id")->value,
          'apiAccess' => $mulesoft_apps_data,
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

  public function resetAppClientSecret($app_id, $mulesoft_id) {
    $mulesoft_connector = \Drupal::service('mulesoft_app.sdk_connector');
    $reset_token = $mulesoft_connector->resetClientSecret($app_id, $mulesoft_id);
    return new JsonResponse($reset_token);
  }

}
