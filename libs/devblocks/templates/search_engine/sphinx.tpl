<div style="padding:5px 10px;">
	Records are fulltext indexed in <a href="http://sphinxsearch.com/" target="_blank">Sphinx</a>, 
	an open source search server built for speed, scalability, and simple integration.
	This option requires Sphinx to be installed and configured separately, but you can expect 
	searches to perform several times faster than MySQL Fulltext.  Additionally, after switching to 
	Sphinx you can drop the related <tt>fulltext_*</tt> tables in Cerb's database to reduce memory 
	and storage usage, and to speed up processes like backups.
</div>

<div style="padding:5px 10px;">
	If you only specify a search index, this content will operate in read-only mode, and Cerb will simply send search 
	queries to Sphinx. You will need to intermittently re-index from Sphinx to see new content.  If you also specify a 
	real-time index, Cerb will automatically send updated records so they are immediately available.  You can set 
	both of these options to a single real-time index, but for the best performance you should implement &quot;main + delta&quot; 
	indexes.
</div>

<div style="padding:5px 10px;">
	<b>Host:</b>
	<p style="margin-left:5px;">
		<input type="text" name="params[{$engine->id}][host]" value="{$engine_params.host}" size="45" style="width:100%;" placeholder="127.0.0.1">
	</p>
	
	<b>Port:</b> (SphinxQL)
	<p style="margin-left:5px;">
		<input type="text" name="params[{$engine->id}][port]" value="{$engine_params.port|default:'9306'}" size="6" maxlength="5" placeholder="9306">
	</p>
	
	<b>Search index:</b>
	<p style="margin-left:5px;">
		<input type="text" name="params[{$engine->id}][index]" value="{$engine_params.index}" size="45" style="width:100%;" placeholder="cerb_{$schema->getNamespace()}">
	</p>
	
	<b>Real-time index:</b> (optional)
	<p style="margin-left:5px;">
		<input type="text" name="params[{$engine->id}][index_rt]" value="{$engine_params.index_rt}" size="45" style="width:100%;" placeholder="cerb_{$schema->getNamespace()}_delta">
	</p>
	
	<div style="padding:5px 10px;">
		<b>Max results:</b> <i>(blank for engine default)</i>
		<p style="margin-left:5px;">
			<input type="text" name="params[{$engine->id}][max_results]" value="{$engine_params.max_results}" size="45" style="width:100%;" placeholder="1000">
		</p>
	</div>
	
	<b>Quick search examples:</b> (optional)
	{$examples = implode("\n", $engine->getQuickSearchExamples($schema))}
	<p style="margin-left:5px;">
		<textarea name="params[{$engine->id}][quick_search_examples]" rows="4" cols="45" style="height:75px;width:100%;" placeholder="(leave blank for Sphinx defaults)">{$engine_params.quick_search_examples|default:$examples}</textarea>
		<br>
		<i>(one per line)</i>
	</p>
</div>
