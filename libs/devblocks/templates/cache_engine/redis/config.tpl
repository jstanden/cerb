<div style="padding:5px 10px;">
	(Recommended) Objects are cached in <a href="http://redis.io/" target="_blank" rel="noopener noreferrer">Redis</a>, an open source 
	key-value datastore designed for incredibly fast performance by storing data exclusively in memory (RAM).
	Each object is stored in Redis with a unique "key", and these keys can be distributed between multiple 
	instances of Redis by using a consistent hashing proxy like <a href="https://github.com/twitter/twemproxy" target="_blank" rel="noopener noreferrer">Twemproxy</a>.
	It is also quick and easy to implement primary-replica replication in Redis for high availability.
</div>

<div style="padding:5px 10px;">
	This option requires Redis to be installed and configured separately, but you can expect excellent 
	cache performance even with thousands of objects.  Redis is used by Twitter, Github, Pinterest, Snapchat, StackOverflow, and many other 
	respected web companies.
</div>

<div style="padding:5px 10px;">
	<b>Host:</b>
	<p style="margin-left:5px;">
		<input type="text" name="params[{$cacher->id}][host]" value="{$cacher_config.host}" size="45" style="width:100%;" placeholder="127.0.0.1">
	</p>
	
	<b>Port:</b>
	<p style="margin-left:5px;">
		<input type="text" name="params[{$cacher->id}][port]" value="{$cacher_config.port|default:'6379'}" size="6" maxlength="5" placeholder="6379">
	</p>
	
	<b>Auth:</b> (optional)
	<p style="margin-left:5px;">
		<input type="text" name="params[{$cacher->id}][auth]" value="{$cacher_config.auth}" size="45" style="width:100%;" placeholder="">
	</p>
	
	<b>Database #:</b> (optional; leave blank when using a proxy)
	<p style="margin-left:5px;">
		<input type="text" name="params[{$cacher->id}][database]" value="{$cacher_config.database}" size="3" maxlength="4" placeholder="0">
	</p>
	
	<b>Key prefix:</b> (optional)
	<p style="margin-left:5px;">
		<input type="text" name="params[{$cacher->id}][key_prefix]" value="{$cacher_config.key_prefix}" size="45" style="width:100%;" placeholder="{literal}{namespace}{/literal}">
	</p>
</div>
