<?php

namespace Drupal\mulesoft_app\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\mulesoft_app\Form\AddappForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 * @Block(
 *   id = "mulesoft_app_mulesoft_app_block",
 *   admin_label = @Translation("Custom my app block")
 * )
 */
class AddappFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $typeofapigateway = \Drupal::config('mulesoft_app.apigatewaytype')
      ->get('typeofapigateway');
    switch ($typeofapigateway) {
      case 'amazonapigateway':
        $output['form'] = $this->formBuilder->getForm(\Drupal\mulesoft_app\Form\AddappamazongatewayForm::class);
        break;
      case 'mulesoftapigateway':
        $output['form'] = $this->formBuilder->getForm(\Drupal\mulesoft_app\Form\MulesoftAppCreateForm::class);
        break;
    }
    return $output;
  }

}
