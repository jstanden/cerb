<div style="padding:5px 10px;">
	Records are fulltext indexed in <a href="http://elasticsearch.org/" target="_blank" rel="noopener noreferrer">Elasticsearch</a>, 
	an open source search server built for speed, scalability, and simple integration.
	This option requires Elasticsearch to be installed and configured separately, but you can expect 
	searches to perform several times faster than MySQL Fulltext.  Additionally, after switching to 
	Elasticsearch you can drop the related <tt>fulltext_*</tt> tables in Cerb's database to reduce memory 
	and storage usage, and to speed up processes like backups.
</div>

<div style="padding:5px 10px;">
	Unlike MySQL Fulltext, Elasticsearch doesn't require indexes or a schema to be defined ahead of time. 
	Cerb will automatically create a new type for each record under the parent index you define here.
</div>

<div style="padding:5px 10px;">
	<b>Base URL:</b>
	<p style="margin-left:5px;">
		<input type="text" name="params[{$engine->id}][base_url]" value="{$engine_params.base_url}" size="45" style="width:100%;" placeholder="http://127.0.0.1:9200/">
	</p>
	
	<b>Version:</b>
	<p style="margin-left:5px;">
		<label><input type="radio" name="params[{$engine->id}][version]" value="5" {if $engine_params.version != '6'}checked="checked"{/if}> 5.x or earlier</label>
		<label><input type="radio" name="params[{$engine->id}][version]" value="6" {if $engine_params.version == '6'}checked="checked"{/if}> 6.x to 7.x</label>
		<label><input type="radio" name="params[{$engine->id}][version]" value="8" {if $engine_params.version == '8'}checked="checked"{/if}> 8.x or later</label>
	</p>
	
	<b>Search index:</b>
	<p style="margin-left:5px;">
		<input type="text" name="params[{$engine->id}][index]" value="{$engine_params.index}" size="45" style="width:100%;" placeholder="cerb">
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
		<textarea name="params[{$engine->id}][quick_search_examples]" rows="4" cols="45" style="height:75px;width:100%;" placeholder="(leave blank for Elasticsearch defaults)">{$engine_params.quick_search_examples|default:$examples}</textarea>
		<br>
		<i>(one per line)</i>
	</p>
</div>
