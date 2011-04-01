<div class="drag">
<span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;cursor:move;"></span>
<input type="text" name="contact_followup[{$uniq_id}][]" size="45" value="{$q}"> 
<select name="contact_followup_fields[{$uniq_id}][]">
	<option value="">-- {$translate->_('portal.sc.cfg.append_to_message')} --</option>
	<optgroup label="{'common.custom_fields'|devblocks_translate}">	
		{foreach from=$ticket_fields item=f key=f_id}
		{assign var=field_group_id value=$f->group_id}
		<option value="{$f_id}" {if $f_id==$field_id}selected{/if}>
			{if isset($groups.$field_group_id)}{$groups.$field_group_id->name}: {/if}{$f->name}
			{assign var=field_type value=$f->type}
			{if isset($field_types.$field_type)}({$field_types.$field_type}){/if}
		</option>
		{/foreach}
	</optgroup>
</select>
<button type="button" onclick="$(this).closest('div.drag').remove();"><span class="cerb-sprite2 sprite-minus-circle-frame"></span></button>
</div>
