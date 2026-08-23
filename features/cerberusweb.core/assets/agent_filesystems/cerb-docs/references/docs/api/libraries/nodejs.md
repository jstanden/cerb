---
id: "docs-api-libraries-nodejs"
title: "Cerb Web-API Library for Node.js"
url: "https://cerb.ai/docs/api/libraries/nodejs/"
summary: "This page provides a detailed overview of the Cerb Web-API Library for Node.js, which is designed to facilitate interaction with the Cerb API. It includes the source code for the `cerb.lib.js` module, which allows Node.js applications to perform API requests such as GET, POST, PUT, and DELETE. The page explains how to set up the library by including the file in a script, initializing it with `require()`, and configuring API credentials using the `setCredentials()` function. It also provides example usage, demonstrating how to make API calls to retrieve data, such as worker information, using the library's methods. The page emphasizes the ease of integrating Cerb's API into Node.js applications through this library."
tags: ["docs"]
---
This module can be included by **Node.js** applications to simplify interaction with the **Cerb** API.

# cerb.lib.js

```
/***********************************************************************
Cerb Web-API Library for Node.js
(c) Copyright 2017 WebGroup Media LLC

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
***********************************************************************/

var request = require('request');
var url_parser = require('url');
var querystring = require('querystring');
var md5 = require('md5');

var _access_key = '';
var _secret_key = '';
var _base_url = '';

var start = function () {
}

var setCredentials = function(base_url, access_key, secret_key) {
	_base_url = base_url;
	_access_key = access_key;
	_secret_key = secret_key;

	return true;
}

var _request = function(method, path, payload, callback) {
	var date = new Date().toUTCString();

	url_parts = url_parser.parse(_base_url + path);

	var string_to_sign = 
		method + "\n" 
		+ date + "\n" 
		+ (url_parts.pathname ? url_parts.pathname : '') + "\n" 
		+ (url_parts.query ? url_parts.query : '') + "\n" 
		+ querystring.stringify(payload) + "\n" 
		+ md5(_secret_key) + "\n"
		;

	var options = {
		url: url_parser.format(url_parts),
		headers: {
			'Date': date,
			'Cerb-Auth': _access_key + ':' + md5(string_to_sign)
		},
		encoding: 'UTF-8',
	};

	http_func = null;

	if(method == 'GET') {
		http_func = request.get;

	} else if(method == 'POST') {
		http_func = request.post;
		options.form = payload;

	} else if(method == 'PUT') {
		http_func = request.put;
		options.form = payload;

	} else if(method == 'DELETE') {
		http_func = request.del;
	}

	if(null != http_func) {
		http_func(options, function(err, res, body) {
			json = JSON.parse(body);
			callback(err, res, json);
		});

	} else {
		callback(true, null, null);
	}
}

var _get = function(path, callback) {
	_request('GET', path, [], callback);
}

var _post = function(path, payload, callback) {
	_request('POST', path, payload, callback);
}

var _put = function(path, payload, callback) {
	_request('PUT', path, payload, callback);
}

var _delete = function(path, callback) {
	_request('DELETE', path, [], callback);
}

var _getAccessKey = function() {
	return _access_key;
}

exports.start = start;
exports.setCredentials = setCredentials;
exports.get = _get;
exports.post = _post;
exports.put = _put;
exports.delete = _delete;
exports.getAccessKey = _getAccessKey;
```

# Usage

- Include the `cerb.lib.js` file in your script.
- Instance `cerb` using `require()`
- Pass your API credentials to `cerb.setCredentials()`
- Make API calls using `cerb.get()`, `cerb.post()`, etc.

```
var cerb = require('./cerb.lib.js');

var cerb_base_url = "https://cerb.example/rest/";
var cerb_api_key = 'xxxxxxxxxxxxxxx';
var cerb_api_secret = 'xxxxxxxxxxxxxxxxxxxxxxxxxxx';

cerb.setCredentials(cerb_base_url, cerb_api_key, cerb_api_secret);

cerb.get('workers/me.json', function(err, res, worker) {
    console.log(worker);
});
```
