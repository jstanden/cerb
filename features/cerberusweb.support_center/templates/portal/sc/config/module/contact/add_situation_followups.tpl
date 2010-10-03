<input type="text" name="followup[]" size="45" value="{$q|escape}"> 
<select name="followup_fields[]">
	<option value="">-- {$translate->_('portal.sc.cfg.append_to_message')} --</option>
	<optgroup label="{'common.custom_fields'|devblocks_translate}">	
		{foreach from=$ticket_fields item=f key=f_id}
		{assign var=field_group_id value=$f->group_id}
		<option value="{$f_id}" {if $f_id==$field_id}selected{/if}>
			{if isset($groups.$field_group_id)}{$groups.$field_group_id->name}: {/if}{$f->name|escape}
			{assign var=field_type value=$f->type}
			{if isset($field_types.$field_type)}({$field_types.$field_type}){/if}
		</option>
		{/foreach}
	</optgroup>
</select>
<br>
