<?php

/**
 * @file
 * Contains \Drupal\jenkins\Form\JenkinsServiceForm.
 */

namespace Drupal\jenkins\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Jenkins Service form.
 */
class JenkinsServiceForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jenkins_service_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['jenkins.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('jenkins.settings');

    $form['base_url'] = [
      '#type' => '#url',
      '#title' => $this->t('Jenkins server URL'),
      '#description' => $this->t('HTTP auth credentials can be included in the URL, like so "http://user:pass@example.com:8080". Note: The value isn\'t encrypted when stored'),
      '#default_value' => $config->get('base_url'),
      '#required' => TRUE,
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['jenkins_test'] = [
      '#type' => 'submit',
      '#value' => $this->t('Test connection'),
      '#validate' => ['::validateUrl'],
      '#submit' => ['::testConnection'],
    ];
    $form['actions']['jenkins_save'] = [
      '#type' => 'submit',
      '#validate' => ['::validateUrl'],
      '#value' => $this->t('Save'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Replace drupal_set_message() with an injectable service in 8.1.x.
   *
   * @see https://www.drupal.org/node/2278383
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('jenkins.settings')
      ->set('base_url', $form_state->getValue('base_url'))
      ->save();

    drupal_set_message($this->t('The configuration options have been saved.'));
  }

  /**
   * Validate if Drupal is able to access the given URL.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @todo Replace drupal_http_request() with Guzzle.
   *
   * @see https://www.drupal.org/node/2673940
   */
  public function validateUrl(array &$form, FormStateInterface $form_state) {
    $response = drupal_http_request($form_state['values']['jenkins_base_url']);
    if (!empty($response->error)) {
      $values = array(
        '@url' => $form_state->getValue('base_url'),
        '@error' => $response->error,
      );
      $form_state->setErrorByName('base_url', $this->t('Unable to contact jenkins server at "@url". Response: "@error".', $values));
    }
  }

  /**
   * Jenkins connection test button submit handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @todo Replace drupal_set_message() with an injectable service in 8.1.x.
   *
   * @see https://www.drupal.org/node/2278383
   */
  public function testConnection(array &$form, FormStateInterface $form_state) {
    $config = $this->config('jenkins.settings');
    drupal_set_message($this->t('Drupal was able to connect to @url.', array('@url' => $form_state['values']['jenkins_base_url'])));
    if ($form_state['values']['jenkins_base_url'] != $config->get('base_url')) {
      drupal_set_message($this->t('New URL value has not been saved. Please use the "Save" button to save the value permanently.'), 'warning');
    }
  }

}
