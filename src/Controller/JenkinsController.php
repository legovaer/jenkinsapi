<?php

/**
 * @file
 * Contains \Drupal\jenkins\Controller\JenkinsController.
 */

namespace Drupal\jenkins\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller routines for Jenkins API routes.
 */
class JenkinsController extends ControllerBase {

  /**
   * Jenkins log page callback.
   *
   * @param string $name
   *   The name of the project being build.
   * @param int $build_id
   *   The id of the build.
   */
  public function logPage($name, $build_id) {
    $response = NULL;
    $markup = NULL;
    jenkins_build_get($name, $build_id, $response);

    if (200 != $response->code) {
      $markup = $this->t('Log not available at this time.');
    }

    $json = json_decode($response->data);

    if (!$json->building) {
      $log_response = NULL;
      if (!jenkins_build_get_log($name, $build_id, $log_response)) {
        $markup = '<h2>' . t('Error retrieving log.') . '</h2>';
      }

      $markup = '<h2>' . t('Build complete.') . "</h2>\n<pre>" . check_plain($log_response->data) . '</pre>';
    }

    if (is_null($markup)) {
      $markup = '<div id="jenkins-log"><h2>' . t('Build running') . '</h2><pre></pre><div id="jenkins-throbber">&nbsp;</div></div>';
    }

    return [
      '#theme' => 'markup',
      '#markup' => $markup,
      '#attached' => [
        'library' => [
          'jenkins/buildstatus'
        ],
        'drupalSettings' => [
          'jenkins' => [
            'name' => $name,
            'build_id' => (int) $build_id,
            'offset' => 0,
          ],
        ],
      ],
    ];
  }

  /**
   * Streams a Jenkins build log.
   *
   * @param string $name
   *   The name of the project being build.
   * @param int $build_id
   *   The id of the build.
   * @param int $offset
   *   The offset being used.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|\Symfony\Component\HttpFoundation\JsonResponse
   */
  public function stream($name, $build_id, $offset = 0) {
    $response = NULL;
    jenkins_request("/job/{$name}/{$build_id}/logText/progressiveText", $response, array('start' => $offset));
    if (200 != $response->code) {
      return $this->t('Log not available at this time');
    }

    $done = TRUE;
    if (!empty($response->headers['x-more-data'])) {
      $done = FALSE;
    }

    $log = $response->data;
    $offset = (int) $response->headers['x-text-size'];

    $output = [
      'done' => $done,
      'log' => $log,
      'offset' => $offset,
    ];

    return new JsonResponse($output);
  }

}