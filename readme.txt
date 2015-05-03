=== BlaatLogin OAuth ===
Contributors: GromBeestje
Donate link: http://code.blaatschaap.be/donations/
Tags: oauth, authentication, sso
Stable tag: trunk
Requires at least: 3.7.0
Tested up to: 4.2.1
License: BSD
License URI: http://opensource.org/licenses/BSD-3-Clause

This plugin turns your WordPress website into an OAuth Consumer.
It allowsallows your users to sign in with any OAuth provider.

== Description ==

The OAuth plugin for WordPress allows you to provide authentication against any
OAuth provider. This plugin is uses the OAuth library by Manuel Lemos. The
plugin allows the admin to select any from the services supported
out-of-the-box by the said library. Additionally it is possible to configure a
service manually, which means any site supporting the OAuth protocol can
be used.


== Installation ==

Plugins are usually installed through the WordPress admin panel, which is
an automated process. If manual installation is desired, extract the archive
and upload the files and directories to
/path/to/your/webroot/wp-content/plugins/

== Changelog ==

0.4.4:
Major rewrite. Much of the code in this release was intended to be released 
only with version 0.5. However, the issues discovered leading to the 0.4.3
release made me decided to create a release before the rewrite was completely
done. This version should work reliable on all supported OAuth services. At
this point, not all services can be supported. Pre-configured services are
Facebook, github, Google, Linkedin Microsoft, and Twitter. Other services
can be configured manually, but at the moment only those that use a flat
data structure. Nested data structures will be supported in a future release.
Data migration for earlier releases is included, but at this moment limited
to Facebook, github, Google, Linkedin Microsoft, and Twitter.


0.4.3:
Database query fix. $wpdb->prepare was called with %d and a string as
parameter. This error conceiled a number of other bugs. Any version
prior to this release is considered unreliable. Due a design flaw any
release only works with few providers. Known to work are Twitter and github.

0.4.2: 
Bugfixes in the Registration and Linking code. 

No more PHP notices when WordPress is running in DEBUG mode. 

When a user is deleted from WordPress, also delete the user's OAuth sessions
from the database.

0.4.1:
Bugfix: the sort order of the buttons was ignored.

0.4: 
Structural rewrite to support multiple authentication frameworks. This
is a preparation for planned plugins to support OpenID 2.0 and BrowserID.

Fixes for the registration using an OAuth provider. In previous versions
signing up using an OAuth provider was not working properly. Also 
improved linking code.

Added logos for some OAuth services, logos included in this release are
bitbucket, bitly, dropbox, etsy, facebook, flickr, github, google, 
linkedin, meetup, microsoft, paypal, tumblr, twitter, vimeo, vk,
wordpress, xing, yahoo, yandex.
  

0.3.6 : 

Bugfix release: options page not found message fixed. This error 
  occured in the General Auth Settings page upon saving. This resulted
  that the pages for login/link/register could not be set.

0.3.5 :

Bugfix: Previous versions allowed a logged in user to link to an already
		linked service.

Field for configuring OAuth services are wider.
Started rewrite to abstract generic Authorisation code, to allow future support
for other Authorisation methods


0.3 :

Rewritten button code. It no longer uses "CSS3 Social Sign-in Buttons by 
Nicolas Gallagher". 
You can now upload your own logos. Adding custom CSS is also possible.

0.2 :

Bugfix: BlaatSchaap Plugins Overview page was missing, and displayed an
        error message when trying to access it.

Synchronised with the OAuth library. The library uses an external
configuration file now. This configuration file is parsed and all
new services are listed.

0.1 :

Initial release

