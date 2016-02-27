# Jenkins module for Drupal.

This API module allows you to integrate Jenkins jobs with your Drupal site.
The modules doesn't expose any UI to end users.  Developers can use this module 
to easily integrate Jenkins with their module.

The module exposes a collection of rules actions so Jenkins builds and other
tasks can be triggered by rules events.  Do not use the deprecated 
"jenkins\_request" action as this will be removed in the RC1 release.

The default configuration for this module assumes that your Jenkins server is
listening at http://localhost:8080.  If your server is running elsewhere, then
you need to set the "jenkins\_base\_url" variable, with drush vset or add the
following line in your settings.php file.

    $conf['jenkins_base_url'] = 'http://jenkins.example.com:8080';

Alternatively you can configure the jenkins server at admin/config/services/jenkins.
The admin page allows you to check connectivity to the jenkins server.

Initial development of the Jenkins API module was sponsored by Technocrat. The
port of the Drupal 8 version of this module was sponsored by
[Capgemini](https://www.drupal.org/capgemini). This module is maintained by
[Dave Hall Consulting](http://davehall.com.au)
