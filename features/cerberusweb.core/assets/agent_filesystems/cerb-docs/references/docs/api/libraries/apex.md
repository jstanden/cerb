---
id: "docs-api-libraries-apex"
title: "Cerb Web-API Library for Apex"
url: "https://cerb.ai/docs/api/libraries/apex/"
summary: "This page provides a detailed overview of the Cerb Web-API Library for Apex, designed to facilitate Salesforce applications in interacting with the Cerb API. It includes the source code for the Cerb.apxc class, which allows developers to perform HTTP requests (GET, POST, PUT, DELETE) to the Cerb API. The page outlines how to set up API credentials and make API calls within a Salesforce environment. It also includes a sample usage section demonstrating how to configure the API base URL, access key, and secret key, and how to execute a POST request to search for tickets. The code is provided under a permissive license, allowing for modification and distribution."
tags: ["docs"]
---
This class can be included by **Salesforce** applications to simplify interaction with the **Cerb** API.

## Cerb.apxc

```
/***********************************************************************
  Cerb for Salesforce
  (c) Copyright 2017 WebGroup Media LLC
  @author Jeff Standen <jeff@webgroupmedia.com>

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

public class CerbApiTest {
  public static String apiBaseUrl {get;set;}
  public static String apiAccessKey {get;set;}
  public static String apiSecretKey {get;set;}

  private static String md5(String text) {
    return EncodingUtil.convertToHex(Crypto.generateDigest('MD5', Blob.valueOf(text)));
  }

  public static HttpResponse get(String path) {
    return Cerb.connect('GET', path);
  }

  public static HttpResponse put(String path) {
    return Cerb.connect('PUT', path);
  }

  public static HttpResponse post(String path) {
    return Cerb.connect('POST', path);
  }

  public static HttpResponse del(String path) {
    return Cerb.connect('DELETE', path);
  }

  public static HttpResponse connect(String http_method, String url_path) {
    Http http = new Http();
    HttpRequest req = new HttpRequest();
  
    String http_date = DateTime.now().format('EEE, d MMM yyy HH:mm:ss Z');
  
    URL url = new URL(apiBaseUrl + url_path);
  
    req.setEndpoint(url.toExternalForm());
    req.setMethod(http_method);
    req.setHeader('Date', http_date);
  
    String url_query = url.getQuery();
  
    if(!String.isNotBlank(url_query))
      url_query = '';
  
    String string_to_sign =
      http_method + '\n'
      + http_date + '\n'
      + url.getPath() + '\n'
      + url_query + '\n'
      + '' + '\n'
      + md5(apiSecretKey) + '\n'
      ;
  
    //System.debug('String to sign: ' + string_to_sign);
  
    req.setHeader('Cerb-Auth', apiAccessKey + ':' + md5(string_to_sign));
  
    //System.debug('Cerb-Auth: ' + apiAccessKey + ':' + md5(string_to_sign));
  
    try {
      HTTPResponse res = http.send(req);
      return res;
    
    } catch(System.CalloutException e) {
      // Handle the error
    }
  
    return null;
  }
}
```

# Usage

- Include the `Cerb.apxc` file in your Salesforce application.
- Set your API credentials.
- Make API calls using `Cerb.get()`, `Cerb.post()`, etc.

```
Cerb.setApiBaseUrl('https://example.cerb.me/rest/');
Cerb.setApiAccessKey('xxxxxxxxxxxx');
Cerb.setApiSecretKey('xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx')

HttpResponse res = Cerb.post('tickets/search.json?q='
  + EncodingUtil.urlEncode(orgId, 'UTF-8')
  + '&show_meta=0&sortAsc=0&sortBy=updated')
  ;

if(null != res) {
  String http_body = res.getBody();
  
  JSONParser parser = JSON.createParser(http_body);
  
  Map<String,Object> results =
    Map<String,Object>) JSON.deserializeUntyped(http_body);
  
  Integer count = (Integer) results.get('count');
  List<Object> rows = (List<Object>) results.get('results');
}
```
