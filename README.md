# SearchWP Finnish Base Forms

[![Latest Stable Version](https://poser.pugx.org/joppuyo/searchwp-finnish-base-forms/v/stable)](https://packagist.org/packages/joppuyo/searchwp-finnish-base-forms) [![Build Status](https://travis-ci.org/joppuyo/searchwp-finnish-base-forms.svg?branch=master)](https://travis-ci.org/joppuyo/searchwp-finnish-base-forms)

SearchWP plugin to add Finnish base forms into search index using [Voikko](https://voikko.puimula.org/).

You can either use **Node.js application** to access Voikko over HTTP or use a locally installed **voikkospell command line application** to lemmatize the indexed terms. Special thanks to [siiptuo](https://github.com/siiptuo) for contributing voikkospell support for this plugin!

The CLI application is much faster because it doesn't have the overhead of performing a HTTP request.

## Requirements

* SearchWP 2.5 or later
* PHP 5.5.9
* A server with either Node.js and about 1GB of spare RAM or voikkospell command line application installed

## Installation

1. **Download** latest version from the [releases](https://github.com/joppuyo/searchwp-finnish-base-forms/releases) tab
2. **Unzip** the plugin into your `wp-content/plugins` directory
3. **Activate** SearchWP Finnish Base Forms from your Plugins page

### Node.js web API

1. Install and start [Voikko Node.js web API](https://github.com/joppuyo/voikko-node-web-api).
2. Go on the Plugins page, find the plugin, click **Settings** and enter the Node API URL there

### Voikkospell command line

1. Install voikkospell on your server. On Ubuntu/Debian this can be done with `apt install libvoikko-dev voikko-fi`
2. Go on the Plugins page, find the plugin, click **Settings**. For **API Type** select **Voikko command line**.

After installation, remember to re-index the site from SearchWP settings page.

