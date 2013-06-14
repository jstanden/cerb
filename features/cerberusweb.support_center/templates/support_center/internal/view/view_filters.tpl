<input type="hidden" name="id" value="{$view->id}">
<div style="float:left;width:60%;">
	<fieldset>
		<legend>Filters</legend>
		
		<ul style="list-style:none;margin:0px;padding:0px;">
			{$fields = $view->getFields()}
			{$params = $view->getEditableParams()}
			{foreach from=$params item=param key=param_key}
			{if isset($fields.$param_key) && !empty($fields.$param_key->db_label)}
			<li>
				<label><input type="checkbox" name="filters[]" value="{$param_key}"> {$fields.$param_key->db_label|capitalize} is <b>{$view->renderCriteriaParam($param)}</b></label> 
			</li>
			{/if}
			{/foreach}
		</ul>
		
		<select name="do" onchange="ajaxHtmlPost('FORM#filters_{$view->id}','FORM#filters_{$view->id}','{devblocks_url}c=ajax&a=viewFiltersDo{/devblocks_url}');">
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
		<select name="field" onchange="ajaxHtmlPost('FORM#filters_{$view->id}','FORM#filters_{$view->id} DIV.filter_fov','{devblocks_url}c=ajax&a=viewFilterGet{/devblocks_url}');">
			<option value="">-- choose --</option>
			{$fields = $view->getSearchFields()}
			{foreach from=$fields item=field key=field_key}
			<option value="{$field_key}">{$field->db_label|capitalize}</option>
			{/foreach}
		</select>
		
		<div class="filter_fov"></div>
		
		<button type="button" onclick="ajaxHtmlPost('FORM#filters_{$view->id}','FORM#filters_{$view->id}','{devblocks_url}c=ajax&a=viewFilterAdd{/devblocks_url}');">Add Filter</button>
	</fieldset>
</div>
<br style="clear:both;">

{if $reload_view}
<script type="text/javascript">
	ajaxHtmlGet('#view{$view->id}','{devblocks_url}c=ajax&a=viewRefresh{/devblocks_url}?id={$view->id}');
</script>
{/if}