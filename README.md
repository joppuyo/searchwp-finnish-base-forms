# SearchWP Finnish Base Forms

SearchWP plugin to add Finnish base forms into search index using [Voikko](https://voikko.puimula.org/).

## Requirements

* Server with Node.js and about 1GB of spare RAM
* SearchWP 2.5 or later
* PHP 5.3

## Installation

1. Clone this plugin into **wp-content/plugins**
2. Go to **wp-content/plugins/searchwp-finnish-base-forms** and run **composer install**
3. SSH into your web server and navigate to **wp-content/plugins/searchwp-finnish-base-forms/node**.
4. Copy **config.sample.js** into **config.js**
5. Run **npm install && npm install -g pm2 && pm2 start index.js**
6. **Activate** SearchWP Finnish Base Forms from your Plugins page
7. Go on the Plugins page, find the plugin, click **Settings** and enter the Node API URL there

