=== SearchWP Finnish Base Forms ===
Contributors: joppuyo
Tags: searchwp, finnish, stem, stemming, lemmatization, lemmatisation
Requires at least: 4.9.4
Tested up to: 4.9.4
Requires PHP: 5.5.9 or greater
License: License: GPLv3 or later

SearchWP plugin to add Finnish base forms in search index

== Description ==
SearchWP plugin to add Finnish base forms in search index. Requires Node.js or voikkospell CLI application and SearchWP 2.5 or later.

== Installation ==
1. Clone this plugin into **wp-content/plugins**
2. **Activate** SearchWP Finnish Base Forms from your Plugins page
3. Either install [Node.js application](https://github.com/joppuyo/voikko-node-web-api) or voikkospell command line application
4. Configure plugin in **Plugins** and **Settings** under **SearchWP Finnish Base Forms**

== Changelog ==

= 2.2.2 =
* Fix locale query logic

= 2.2.1 =
* Get UTF-8 locale from system
* Ensure binary has correct permissions

= 2.2.0 =
* Add option to use bundled voikkospell binary on Linux systems

= 2.1.1 =
* Always show split compound words option in settings

= 2.1.0 =
* Add option to split compound words

= 2.0.3 =
* Update release file name and readme

= 2.0.2 =
* Add Travis to build process

= 2.0.1 =
* Fix crash due to missing parameter

= 2.0.0 =
* Move Node.js code into its own repository

= 1.1.2 =
* Index multiple base forms also when using web API
* Change symfony/process version so it can be installed on both PHP 5 and PHP 7

= 1.1.1 =
* Fix issue where sometimes there were too few search results

= 1.1.0 =
* Allow using local voikkospell command line application instead of the Node web API

= 1.0.3 =
* Fix issue with “Add base forms to search query” option

= 1.0.2 =
* Add option to add base forms to search queries entered by users
* Normalize API URL trailing slash

= 1.0.1 =
* Allow using local plugin Composer or global Bedrock Composer

= 1.0.0 =
* Initial release
