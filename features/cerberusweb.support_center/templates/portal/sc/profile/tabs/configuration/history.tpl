{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="community_portal">
<input type="hidden" name="action" value="saveConfigTabJson">
<input type="hidden" name="portal_id" value="{$portal->id}">
<input type="hidden" name="config_tab" value="history">

<div class="cerb-worklist-columns">
	<div>
		<b>Worklist columns:</b> (leave blank for default)
	</div>
	
	{foreach from=$history_columns item=column key=token}
	{$selected = in_array($token, $history_params.columns)}
	<div style="margin:3px;" class="column">
		<label>
			<span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;cursor:move;" title="Click and drag to rearrange"></span>
			<input type="checkbox" name="history_columns[]" value="{$token}" {if $selected}checked="checked"{/if}>
			{if $selected}
			<b>{$column->db_label|capitalize}</b>
			{else}
			{$column->db_label|capitalize}
			{/if}
		</label>
	</div>
	{/foreach}
</div>

<div class="status"></div>

<button type="button" class="submit" style="margin-top:10px;"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $status = $frm.find('div.status');
	var $container = $frm.find('div.cerb-worklist-columns');
		
	$container
		.sortable({
			items: 'DIV.column',
			placeholder:'ui-state-highlight'
		})
		;
	;
	
	$frm.find('button.submit').on('click', function(e) {
		genericAjaxPost($frm, '', null, function(json) {
			if(json && typeof json == 'object') {
				if(json.error) {
					Devblocks.showError($status, json.error);
				} else if (json.message) {
					Devblocks.showSuccess($status, json.message);
				} else {
					Devblocks.showSuccess($status, "Saved!");
				}
			}
		});
	});
});
</script>
