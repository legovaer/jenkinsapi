<?php

/**
 * @file
 * Jenkins administration related functions.
 */

/**
 * Jenkins settings form.
 */
function jenkins_settings($form, &$form_state) {

  $form['jenkins_base_url'] = array(
    '#type' => 'textfield',
    '#title' => t('Jenkins server URL'),
    '#description' => t('HTTP auth credentials can be included in the URL, like so "http://user:pass@example.com:8080". Note: The value isn\'t encrypted when stored'),
    '#default_value' => \Drupal::config('jenkins.settings')->get('base_url'),
    '#required' => TRUE,
  );

  $form['actions'] = array('#type' => 'actions');

  $form['actions']['jenkins_test'] = array(
    '#type' => 'submit',
    '#value' => t('Test connection'),
    '#submit' => array('jenkins_settings_test_submit'),
  );

  $form['actions']['jenkins_save'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

/**
 * Jenkins settings form validation handler.
 */
function jenkins_settings_validate($form, &$form_state) {
  if (!valid_url($form_state['values']['jenkins_base_url'], TRUE)) {
    form_error($form['jenkins_base_url'], t('"@url" is not a valid URL.', array('@url' => $form_state['values']['jenkins_base_url'])));
    return FALSE;
  }

  $response = drupal_http_request($form_state['values']['jenkins_base_url']);
  if (!empty($response->error)) {
    $values = array(
      '@url' => $form_state['values']['jenkins_base_url'],
      '@error' => $response->error,
    );
    form_error($form['jenkins_base_url'], t('Unable to contact jenkins server at "@url". Response: "@error".', $values));
  }
}

/**
 * Jenkins settings form submit handler.
 */
function jenkins_settings_submit($form, &$form_state) {
  $config = \Drupal::service('config.factory')->getEditable('jenkins.settings');

  $config->set('base_url', $form_state['values']['jenkins_base_url']);
  $config->save();
  // Lifted from system_settings_form_submit().
  drupal_set_message(t('The configuration options have been saved.'));
}

/**
 * Jenkins connection test button submit handler.
 */
function jenkins_settings_test_submit($form, &$form_state) {
  $current = \Drupal::config('jenkins.settings')->get('base_url');
  drupal_set_message(t('Drupal was able to connect to @url.', array('@url' => $form_state['values']['jenkins_base_url'])));
  if ($form_state['values']['jenkins_base_url'] != $current) {
    drupal_set_message(t('New URL value has not been saved. Please use the "Save" button to save the value permenantly.'), 'warning');
  }
}
