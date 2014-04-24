=== BlaatSchaap OAuth ===
Contributors: GromBeestje
Donate link: http://code.blaatschaap.be/donations/
Tags: oauth, authentication
Stable tag: trunk
Requires at least: 3.7
Tested up to: 3.9
License: BSD
License URI: http://opensource.org/licenses/BSD-3-Clause

This plugin allows your users to sign in with any OAuth provider.
There are many pre-configured sites such as Facebook, Google and Microsoft.

== Description ==

Please note: tested with 3.7 and 3.8. It will probably work with earlier
versions but this has not been tested.

The OAuth plugin for WordPress allows you to provide authentication against any
OAuth provider. This plugin is uses the OAuth library by Manuel Lemos. The
plugin allows the admin to select any from the services supported
out-of-the-box by the said library. Additionally it is possible to configure a
service manually, which means almost any site supporting the OAuth protocol can
be used.


== Installation ==

Plugins are usually installed through the WordPress admin panel, which is
an automated process. If manual installation is desired, extract the archive
and upload the files and directories to
/path/to/your/webroot/wp-content/plugins/

== Changelog ==
0.3 :

Rewritten button code. It no longer uses "CSS3 Social Sign-in Buttons by Nicolas Gallagher". 
You can now upload your own logos. Adding custom CSS is also possible.

0.2 :

Bugfix: BlaatSchaap Plugins Overview page was missing, and displayed an
        error message when trying to access it.

Synchronised with the OAuth library. The library uses an external
configuration file now. This configuration file is parsed and all
new services are listed.

0.1 :

Initial release

