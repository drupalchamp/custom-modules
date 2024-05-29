<?php

namespace Drupal\aca_custom_importer\Form;

use Drupal\node\Entity\Node;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\taxonomy\Entity\Term;

/**
 * Act Content Import.
 */
class ActContentImportForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   */
  public function getFormId() {
    return 'act_import_form';
  }

  /**
   * Form constructor.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['upload'] = [
      '#title' => $this->t('Choose CSV File'),
      '#type' => 'managed_file',
      '#progress_indicator' => 'throbber',
      '#status' => FILE_STATUS_PERMANENT,
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import ACT Content'),
      '#attributes' => ['class' => ['btn-success', 'btn btn-primary']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fid = $form_state->getValue('upload')[0];
    $file = File::load($fid);
    $uri = $file->getFileURI();
    $row_count = 0;
	$fp = fopen($uri, 'r');
	$header = [
      'Title',
      'Body',
    ];
	$rows = [];
    while (!feof($fp)) {
		$import_row = fgetcsv($fp);
		if (is_array($import_row) && !empty($import_row)) {
            if ($row_count == 0) {
			  $rows[] = $import_row;
			} else {
				if ($header == $rows[0]) {
					if (isset($import_row[0]) && !empty($import_row[0])) {
					  $title = $import_row[0];
					  $query = \Drupal::entityQuery('node');
					  $query->condition('type', 'content_act_story');
					  $query->condition('title', $title);
					  $entity_ids = $query->execute();
					  if (!empty($entity_ids)) {
						$node_obj = Node::loadMultiple($entity_ids);
						foreach ($node_obj as $node) {
						  $node->set('title', $import_row[0]);
						  $node->set('field_body', $import_row[1]);
						  $node->field_body->format = '3';
						  $node->set('group_content_access', '1');
						  $node->save();
						}
						\Drupal::messenger()->addStatus($this->t('Successfully updated @row ACT story content.', ['@row' => $row_count]));
					  }
					}
				}
			}		
		}
	    $row_count++;	
	}


  }
    
}
