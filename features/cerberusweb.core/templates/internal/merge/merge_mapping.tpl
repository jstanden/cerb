{$uniq_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frm{$uniq_id}" name="frm{$uniq_id}">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="records">
<input type="hidden" name="action" value="saveMerge">
<input type="hidden" name="context" value="{$context_ext->id}">
<input type="hidden" name="view_id" value="{$view_id}">
{foreach from=$dicts item=dict}
<input type="hidden" name="ids[]" value="{$dict->id}">
{/foreach}
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>Merge into this {$aliases.singular|lower} record:</legend>
	
	<ul class="bubbles">
		{foreach from=$dicts item=dict name=dicts}
		<li>
			<input type="radio" name="target_id" value="{$dict->id}" {if $smarty.foreach.dicts.first}checked="checked"{/if}>
			{if $context_ext->hasOption('avatars')}
			<img class="cerb-avatar" src="{devblocks_url}c=avatars&context={$context_ext->id}&context_id={$dict->id}{/devblocks_url}?v={$dict->updated_at}">
			{/if}
			<a href="javascript:;" class="cerb-peek-trigger" data-context="{$dict->_context}" data-context-id="{$dict->id}">{$dict->_label} (id:{$dict->id})</a>
		</li>
		{/foreach}
	</ul>
</fieldset>

<fieldset class="peek">
	<legend>Using these field values:</legend>

	<table>
	{foreach from=$field_values item=data key=k}
		{$field_type = $data.type}
		{if 0 != count($data.values)}
		<tr>
			<td align="right" style="padding-right:10px;vertical-align:top;">
				<input type="hidden" name="keys[]" value="{$k}">
				<b>{$data.label|trim}:</b>
				{*({$field_type})*}
			</td>
			<td>
				{if in_array($field_type, ['I','M','X']) == $field_type}
					{foreach from=$data.values item=item_label key=item_key}
						<div>
							<label>
							<input type="checkbox" name="values[{$k}][]" value="{$item_key}" checked="checked">
							{$item_label}
							</label>
						</div>
					{/foreach}
					
				{else}
					{if 1 == count($data.values)}
					{$v = $data.values|@reset}
					{if 'E' == $field_type}
						{if $v}{$v|devblocks_date} ({$v|devblocks_prettytime}){/if}
					{else}
						{$v}
					{/if}
					<input type="hidden" name="values[{$k}]" value="{$data.values|@key}">
					{else}
					<select name="values[{$k}]">
					{foreach from=$data.values item=v key=id name=items}
					<option value="{$id}">
						{if 'E' == $field_type}
							{if $v}{$v|devblocks_date} ({$v|devblocks_prettytime}){/if}
						{elseif 'T' == $field_type}
							{$v|truncate:255}
						{else}
							{$v}
						{/if}
					</option>
					{/foreach}
					</select>
					{/if}
				{/if}
			</td>
		</tr>
		{/if}
	{/foreach}
	</table>
</fieldset>

<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.continue'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#frm{$uniq_id}');
	
	$frm.find('.cerb-peek-trigger').cerbPeekTrigger();

	// Update the selected record label when we change targets
	$frm.find('input[name=target_id]').on('click', function() {
		var id = $(this).attr('value');

		$frm.find('select[name="values[name]"], select[name="values[subject]"], select[name="values[title]"]').each(function() {
			var $select = $(this);

			if($select.find('option[value=' + id + ']').length > 0)
				$select.val(id);
		});
	});
	
	$frm.find('BUTTON.submit').on('click', function() {
		genericAjaxPost('frm{$uniq_id}','popuppeek','');
	});
});
</script>