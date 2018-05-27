# SearchWP Finnish Base Forms

SearchWP plugin to add Finnish base forms into search index using [Voikko](https://voikko.puimula.org/).

You can either use **Node.js application** to access Voikko over HTTP or use a locally installed **voikkospell command line application** to lemmatize the indexed terms.

The CLI application is much faster because it doesn't have the overhead performing a HTTP request.

## Requirements

* SearchWP 2.5 or later
* PHP 5.3
* A server with either Node.js and about 1GB of spare RAM or voikkospell command line application installed

## Installation

1. Clone this plugin into **wp-content/plugins**
2. Go to **wp-content/plugins/searchwp-finnish-base-forms** and run **composer install**
3. **Activate** SearchWP Finnish Base Forms from your Plugins page

### Node.js web API

1. SSH into your web server and navigate to **wp-content/plugins/searchwp-finnish-base-forms/node**.
2. Copy **config.sample.js** into **config.js**
3. Run **npm install && npm install -g pm2 && pm2 start index.js**
4. Go on the Plugins page, find the plugin, click **Settings** and enter the Node API URL there

### Voikkospell command line

1. Install voikkospell on your server. On Ubuntu/Debian this can be done with **apt install libvoikko-dev voikko-fi**
2. Go on the Plugins page, find the plugin, click **Settings**. For **API Type** select **Voikko command line**.

After installation, remember to re-index the site from SearchWP settings page.

