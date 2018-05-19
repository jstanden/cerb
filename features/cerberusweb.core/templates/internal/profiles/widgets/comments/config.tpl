<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset class="peek">
		<legend>Display comments for this record:</legend>
		
		<b>Type:</b>
		
		<div style="margin-left:10px;">
			<select name="params[context]">
				<option value=""></option>
				{foreach from=$context_mfts item=context_mft}
				<option value="{$context_mft->id}" {if $widget->extension_params.context == $context_mft->id}selected="selected"{/if}>{$context_mft->name}</option>
				{/foreach}
			</select>
		</div>
		
		<b>ID:</b>
		
		<div style="margin-left:10px;">
			<input type="text" name="params[context_id]" value="{$widget->extension_params.context_id}" class="placeholders" style="width:95%;padding:5px;border-radius:5px;" autocomplete="off" spellcheck="off">
		</div>
	</fieldset>
		
	<fieldset class="peek">
		<legend>{'common.options'|devblocks_translate|capitalize}:</legend>
		<div>
			<b>Max. Height: <small>(pixels)</small></b>
			
			<div style="margin-left:10px;">
				<input type="text" name="params[height]" value="{$widget->extension_params.height}" class="placeholders" style="width:95%;padding:5px;border-radius:5px;" autocomplete="off" spellcheck="off">
			</div>
		</div>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	var $select = $config.find("select[name='params[context]']");
});
</script>