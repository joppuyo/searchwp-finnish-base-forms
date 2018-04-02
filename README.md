# SearchWP Finnish Base Forms

SearchWP plugin to add Finnish base forms into search index using [Voikko](https://voikko.puimula.org/).

## Requirements

* Server with Node.js and about 1GB of spare RAM
* SearchWP 2.5 or later
* PHP 5.3

## Installation

1. Clone this plugin into **wp-content/plugins**
2. SSH into your web server and navigate to **wp-content/plugins/searchwp-finnish-base-forms/node**.
3. Copy **config.sample.js** into **config.js**
4. Run **npm install && npm install -g pm2 && pm2 start index.js**
3. **Activate** SearchWP Finnish Base Forms from your Plugins page

