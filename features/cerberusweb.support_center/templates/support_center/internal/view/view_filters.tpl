<input type="hidden" name="id" value="{$view->id|escape}">
<div style="float:left;width:60%;">
	<fieldset>
		<legend>Filters</legend>
		
		<ul style="list-style:none;margin:0px;padding:0px;">
			{$fields = $view->getFields()}
			{$params = $view->getEditableParams()}
			{foreach from=$params item=param key=param_key}
			{if isset($fields.$param_key) && !empty($fields.$param_key->db_label)}
			<li>
				<label><input type="checkbox" name="filters[]" value="{$param_key}"> {$fields.$param_key->db_label} is <b>{$view->renderCriteriaParam($param)}</b></label> 
			</li>
			{/if}
			{/foreach}
		</ul>
		
		<select name="do" onchange="ajaxHtmlPost('FORM#filters_{$view->id|escape}','FORM#filters_{$view->id|escape}','{devblocks_url}c=ajax&a=viewFiltersDo{/devblocks_url}');">
			<option value="">-- action --</option>
			<option value="remove">Remove selected filters</option>
			<option value="reset">Reset filters</option>
		</select>
	</fieldset>
</div>
<div style="float:right;width:40%;">
	<fieldset>
		<legend>Add Filter</legend>
		
		<b>Criteria:</b><br>
		<select name="field" onchange="ajaxHtmlPost('FORM#filters_{$view->id|escape}','FORM#filters_{$view->id|escape} DIV.filter_fov','{devblocks_url}c=ajax&a=viewFilterGet{/devblocks_url}');">
			<option value="">-- choose --</option>
			{$fields = $view->getSearchFields()}
			{foreach from=$fields item=field key=field_key}
			<option value="{$field_key}">{$field->db_label}</option>
			{/foreach}
		</select>
		
		<div class="filter_fov"></div>
		
		<button type="button" onclick="ajaxHtmlPost('FORM#filters_{$view->id|escape}','FORM#filters_{$view->id|escape}','{devblocks_url}c=ajax&a=viewFilterAdd{/devblocks_url}');">Add Filter</button>
	</fieldset>
</div>
<br style="clear:both;">

{if $reload_view}
<script type="text/javascript">
	ajaxHtmlGet('#view{$view->id|escape}','{devblocks_url}c=ajax&a=viewRefresh{/devblocks_url}?id={$view->id|escape}');
</script>
{/if}