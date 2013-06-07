{$uniqid = uniqid()}
<fieldset id="fieldset{$uniqid}" class="peek">
	<legend>Worklists</legend>
	
	<b>{'common.add'|devblocks_translate|capitalize}:</b> 
	<select name="add_context">
		<option value="">- {'common.choose'|devblocks_translate|lower} -</option>
		{foreach from=$contexts item=mft key=mft_id}
		{if isset($mft->params['options'][0]['workspace'])}
		<option value="{$mft_id}">{$mft->name}</option>
		{/if}
		{/foreach}
	</select>
	
	{foreach from=$worklists item=worklist name=worklists key=worklist_id}
	{assign var=worklist_view value=$worklist->list_view}
	<div class="column">
		<span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;cursor:move;"></span><!--
		--><a href="javascript:;" onclick="if(confirm('Are you sure you want to delete this worklist?')) { $(this).closest('div').remove(); }"><span class="ui-icon ui-icon-trash" style="display:inline-block;vertical-align:middle;"></span></a><!--
		--><input type="hidden" name="ids[]" value="{$worklist->id}"><!--
		--><input type="text" name="names[]" value="{$worklist_view->title}" size="45"><!--
		--><span>{if isset($contexts.{$worklist->context})}{$contexts.{$worklist->context}->name}{/if}</span>	
	</div>
	{/foreach}
	
</fieldset>

<script type="text/javascript">
var $fieldset = $('fieldset#fieldset{$uniqid}');

$fieldset.find('select[name=add_context]').change(function() {
	var $select = $(this);
	
	if($select.val() == '')
		return;
	
	var $columns = $(this.form).find('fieldset').first();
	var $new_column = $('<div class="column"></div>');
	
	$('<span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;"></span>').appendTo($new_column);
	$('<a href="javascript:;" onclick="if(confirm(\'Are you sure you want to delete this worklist?\')) { $(this).closest(\'div\').remove(); }"><span class="ui-icon ui-icon-trash" style="display:inline-block;vertical-align:middle;"></span></a>').appendTo($new_column);
	$('<input type="hidden" name="ids[]" value="'+$select.val()+'">').appendTo($new_column);
	$('<input type="text" name="names[]" value="'+$select.find(':selected').text()+'" size="45">').appendTo($new_column);
	$('<span>'+$select.find(':selected').text()+'</span>').appendTo($new_column);
	
	$select.val('');

	$new_column.appendTo($columns);
	
	$new_column.find('input:text:first').select().focus();
});
</script>