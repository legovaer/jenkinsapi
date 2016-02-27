<?php

/**
 * @file
 * Contains \Drupal\jenkins\Controller\JenkinsController.
 */

namespace Drupal\jenkins\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jenkins\JenkinsJob;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller routines for Jenkins API routes.
 */
class JenkinsController extends ControllerBase {

  /**
   * @var $jenkins_job JenkinsJob;
   */
  protected $jenkins_job;

  /**
   * {@inheritdoc}
   */
  public function __construct(JenkinsJob $jenkins_job) {
    $this->jenkins_job = $jenkins_job;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('jenkins.job')
    );
  }

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
    try {
      /** @var Response $response */
      $response = $this->jenkins_job->getBuild($name, $build_id);
      $json = json_decode($response->getBody());

      if (!$json->building) {
        try {
          /** @var Response $log_response */
          $log_response = $this->jenkins_job->getBuildLog($name, $build_id);
          $markup = '<h2>' . $this->t('Build complete.') . "</h2>\n<pre>" . $log_response->getBody() . '</pre>';
        }
        catch(Requ $e) {
          $markup = '<h2>' . $this->t('Error retrieving log.') . '</h2>';
        }
      }
    }
    catch(RequestException $e) {
      $markup = $this->t('Log not available at this time.');
    }

    if (is_null($markup)) {
      $markup = '<div id="jenkins-log"><h2>' . t('Build running') . '</h2><pre></pre><div id="jenkins-throbber">&nbsp;</div></div>';
    }

    return [
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
  public function streamPage($name, $build_id, $offset = 0) {
    try {
      $response = $this->jenkins_job->stream($name, $build_id, $offset);
      $output = [
        'done' => empty($response->getHeader('x-more_data')),
        'log' => $response->getBody(),
        'offset' => (int) $response->getHeader('x-text-size'),
      ];
      return new JsonResponse($output);
    }
    catch (RequestException $e) {
      return $this->t('Log not available at this time');
    }
  }

}
