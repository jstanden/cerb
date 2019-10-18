Cerb Plugins - wgm.storage.s3.gatekeeper
===========================================
Copyright (C) 2012 Webgroup Media, LLC.  
[http://cerb.ai/](http://cerb.ai/)  

What's this?
------------
This plugin adds a new Storage Engine to your helpdesk for working with Amazon S3 without directly knowing the required S3 credentials. Instead, it authenticates with a remote script and retrieves a pre-signed URL which it then uses to perform requests.

Installation
------------
* Change directory to **/cerb6/storage/plugins/**
* `git clone git://github.com/cerb-plugins/wgm.storage.s3.gatekeeper.git`
* In your helpdesk, enable the plugin from **Setup->Plugins**.
* Download the gatekeeper development toolkit from https://gist.github.com/1372736
* Configure your Amazon S3 and authentication details in the script
* Create a new Storage Profile from **Setup->Storage->Profiles**.

Credits
-------
This plugin was developed by [Webgroup Media, LLC](https://cerb.ai/).

License
-------

[http://opensource.org/licenses/gpl-2.0.php](http://opensource.org/licenses/gpl-2.0.php)  

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
