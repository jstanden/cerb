<fieldset id="tabConfig{$workspace_tab->id}" class="peek">
<legend>Topic</legend>

<b>Start the knowledgebase browser at this category:</b>
<div>
	<div style="overflow:auto;height:150px;border:solid 1px rgb(180,180,180);background-color:rgb(255,255,255);">
		<label><input type="radio" name="params[topic_id]" value="0" {if !$workspace_tab->params.topic_id}checked="checked"{/if}> - show all - </label> 
		<br>
		{foreach from=$levels item=depth key=node_id}
			<label>
				<input type="radio" name="params[topic_id]" value="{$node_id}" {if $workspace_tab->params.topic_id==$node_id}checked{/if}>
				<span style="vertical-align:middle;padding-left:{math equation="(x-1)*10" x=$depth}px;{if !$depth}font-weight:bold;{/if}">{if $depth}<span class="glyphicons glyphicons-chevron-right" style="color:rgb(80,80,80);"></span>{else}<span class="glyphicons glyphicons-folder-closed" style="font-size:16px;color:rgb(80,80,80);"></span>{/if} <span id="kbTreeCat{$node_id}">{$categories.$node_id->name}</span></span>
			</label>
			<br>
		{/foreach}
	</div>
	
</div> 
</fieldset>

<script type="text/javascript">
$(function() {
	var $fieldset = $('#tabConfig{$workspace_tab->id}');
});
</script>