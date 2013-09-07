Jenkins module for Drupal.

This API module allows you to integrate Jenkins jobs with your Drupal site.
The modules doesn't expose any UI.  Developers can use this module to easily 
integrate Jenkins with their module.  A common use case is rules actions
which call this code.

The default configuration for this module assumes that your Jenkins server is
listening at http://localhost:8080.  If your server is running elsewhere, then
you need to set the 'jenkins_base_url' variable, with drush vset of add the
following line in your settings.php file.

$conf['jenkins_base_url'] = 'http://jenkins.example.com:8080';

Initial development of the Jenkins API module was sponsored by Technocrat. 
This module is maintained by Dave Hall Consulting - http://davehall.com.au
