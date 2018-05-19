=== SearchWP Finnish Base Forms ===
Contributors: joppuyo
Tags: searchwp, finnish, stem, stemming, lemmatization, lemmatisation
Requires at least: 4.9.4
Tested up to: 4.9.4
Requires PHP: 5.3.0 or greater
License: License: GPLv3 or later

SearchWP plugin to add Finnish base forms in search index

== Description ==
SearchWP plugin to add Finnish base forms in search index. Requires Node.js and SearchWP 2.5 or later.

== Installation ==
1. Clone this plugin into **wp-content/plugins**
2. SSH into your web server and navigate to **wp-content/plugins/searchwp-finnish-base-forms/node**. Copy **config.sample.js** into **config.js** Run **npm install && npm install -g pm2 && pm2 start index.js**
3. **Activate** SearchWP Finnish Base Forms from your Plugins page

== Changelog ==
= 1.0.2 =
* Add option to add base forms to search queries entered by users
* Normalize API URL trailing slash

= 1.0.1 =
* Allow using local plugin Composer or global Bedrock Composer

= 1.0.0 =
* Initial release
