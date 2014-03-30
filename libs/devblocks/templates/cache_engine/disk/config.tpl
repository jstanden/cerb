<div style="padding:5px 10px;">
	(Default) Objects are cached as <tt>cache--*</tt> files in the <tt>storage/tmp/</tt> directory of 
	the local filesystem.  This method requires no special configuration, and you can expect a modest 
	performance improvement by offloading file caching to the server's operating system. 
</div>

<div style="padding:5px 10px;">
	For high volume workloads you should install and enable a distributed, memory-based cache like Redis or Memcached.
</div>
