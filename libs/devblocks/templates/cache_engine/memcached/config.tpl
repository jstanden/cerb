<div style="padding:5px 10px;">
	Objects are cached in <a href="http://memcached.org" target="_blank" rel="noopener noreferrer">Memcached</a>, an open source, 
	memory-based caching server designed to optimize dynamic web applications.  Each object is stored with 
	a unique "key", and these keys can be distributed between multiple instances of Memcached by using a 
	consistent hashing proxy like <a href="https://github.com/twitter/twemproxy" target="_blank" rel="noopener noreferrer">Twemproxy</a>.
</div>

<div style="padding:5px 10px;">
	This option requires Memcached to be installed and configured separately, but you can expect excellent cache performance even with thousands of objects.
	Memcached is used by Wikipedia, Flickr, Youtube, Wordpress, Craigslist, and many others.
</div>

<div style="padding:5px 10px;">
	The provided Memcached host:port should be dedicated to Cerb, as cached objects may be deleted at any time.
</div>

<div style="padding:5px 10px;">
	<b>Host:</b>
	<p style="margin-left:5px;">
		<input type="text" name="params[{$cacher->id}][host]" value="{$cacher_config.host}" size="45" style="width:100%;" placeholder="127.0.0.1">
	</p>
	
	<b>Port:</b>
	<p style="margin-left:5px;">
		<input type="text" name="params[{$cacher->id}][port]" value="{$cacher_config.port|default:'11211'}" size="6" maxlength="5" placeholder="11211">
	</p>
	
	<b>Key prefix:</b> (optional)
	<p style="margin-left:5px;">
		<input type="text" name="params[{$cacher->id}][key_prefix]" value="{$cacher_config.key_prefix}" size="45" style="width:100%;" placeholder="{literal}{namespace}{/literal}">
	</p>
</div>
