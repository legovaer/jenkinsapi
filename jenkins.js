(function($) {

  var updateLog = function(data) {

    var e = $('#jenkins-log');

    $('pre', e).append(Drupal.checkPlain(data.log));

    var p = e.parent();
    if ('cboxLoadedContent' == p.attr('id')) {
      p.scrollTop(e.height());
    }
    else {
      var d = $(document);
      d.scrollTop(d.height());
    }

    if (!data.done) {
      Drupal.settings.jenkins.offset = parseInt(data.offset);
      setTimeout(pollLog, 1000);
    }
    else {
      $('#jenkins-throbber', e).hide();
      $('h2', e).text(Drupal.t("Build complete"));
    }
  };

  var pollLog = function() {
    settings = Drupal.settings.jenkins;
    url = Drupal.settings.basePath + '?q=jenkins/stream-log/' + settings.name
      + '/' + settings.build_id + '/' + settings.offset;
    $.get(url, null, updateLog);
  };

  Drupal.behaviors.jenkinsLog = {
    attach: function (context, settings) {
      if ($('#jenkins-log', context).length) {
        pollLog();
      }
    }
  };
})(jQuery);
