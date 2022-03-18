<div style="padding:5px 10px;">
	(Default) Records are fulltext indexed in tables in Cerb's existing MySQL database. 
	Tables are prefixed with <tt>fulltext_*</tt>. No special configuration is required.  This option 
	provides reasonable performance in most situations, but high volume environments should 
	consider using a specialized search engine like Elastic Search or Sphinx instead.
</div>

<div style="padding:5px 10px;">
	<b>Stop words:</b> <i>(one per line)</i>
	<p style="margin-left:5px;">
		<textarea name="params[{$engine->id}][stop_words]" style="height:5em;width:100%;">{$engine_params.stop_words}</textarea>
		<br>
		<i>(These words are ignored in full-text search queries)</i>
	</p>
</div>

<div style="padding:5px 10px;">
	<b>Max results:</b> <i>(blank for engine default)</i>
	<p style="margin-left:5px;">
		<input type="text" name="params[{$engine->id}][max_results]" value="{$engine_params.max_results}" size="45" style="width:100%;" placeholder="1000">
	</p>
</div>