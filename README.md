# SearchWP Finnish Base Forms

SearchWP plugin to add Finnish base forms in search index using [Voikko](https://voikko.puimula.org/).

## Requirements

* Node.js server
* SearchWP 2.5 or later
* About 1GB of free RAM available
* PHP 5.3

## Installation

1. Clone this plugin into **wp-content/plugins**
2. SSH into your web server and navigate to **wp-content/plugins/searchwp-finnish-base-forms/node**.
3. Copy **config.sample.js** into **config.js**
4. Run **npm install && npm install -g pm2 && pm2 start index.js**
3. **Activate** SearchWP Finnish Base Forms from your Plugins page

