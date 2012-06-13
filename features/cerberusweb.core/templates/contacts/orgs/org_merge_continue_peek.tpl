{$uniq_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frm{$uniq_id}" name="frm{$uniq_id}">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="saveOrgMergePeek">
<input type="hidden" name="view_id" value="{$view_id}">
{foreach from=$orgs item=org key=org_id}
<input type="hidden" name="org_id[]" value="{$org->id}">
{/foreach}

<table cellpadding="3" cellspacing="0" border="0">
	{foreach from=$combinations item=combo key=property}
	{$label = $combo.label}
	{$values = $combo.values}

	<tr>
		<td nowrap="nowrap"><b>{$label|devblocks_translate|capitalize}:</b></td>
		<td valign="top">
			{if empty($values)}
				<input type="hidden" name="prop[{$property}]" value="">
			{elseif is_array($values) && 1==count($values)}
				{foreach from=$values item=value key=org_id}
				<input type="hidden" name="prop[{$property}]" value="{$org_id}">{$value}
				{/foreach}
			{else}
				{if 'cf_' == substr($property,0,3)}
					{$cfield_id = substr($property,3)}
					{$cfield_type = $custom_fields.{$cfield_id}->type}
					
					{if $cfield_type == 'X'}
						[auto]
					{else} {* $cfield_type == 'S' || $cfield_type == 'T' || $cfield_type == 'D' || $cfield_type == 'E' || $cfield_type == 'N' || $cfield_type == 'U' || $cfield_type == 'C' || $cfield_type == 'W' *}
						<select name="prop[{$property}]">
						{foreach from=$values item=value key=org_id}
							{if $cfield_type == 'E'}
							<option value="{$org_id}">{$value|devblocks_date} ({$value|devblocks_prettytime})</option>
							{else}
							<option value="{$org_id}">{$value}</option>
							{/if}
						{/foreach}
						</select>
					{/if}
				{else}
					<select name="prop[{$property}]">
					{foreach from=$values item=value key=org_id}
						<option value="{$org_id}">{$value}</option>
					{/foreach}
					</select>
				{/if}
			{/if}
		</td>
	</tr>
	{/foreach}
</table>
<br>

{*if $active_worker->hasPriv('core.addybook.org.actions.update')*}
	<button type="button" onclick="genericAjaxPopupPostCloseReloadView(null,'frm{$uniq_id}','{$view_id}',false,'org_merge');"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.continue')|capitalize}</button>
{*/if*}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title', "Merge Organizations");
		
		$(this).find("select:first").focus();
	});
	// Autocomplete
	$('#frm{$uniq_id} button.chooser_orgs').each(function() {
		ajax.chooser(this,'cerberusweb.contexts.org','org_id', { autocomplete:true });
	});
</script>