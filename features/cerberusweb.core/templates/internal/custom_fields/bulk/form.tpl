<table cellspacing="0" cellpadding="2" width="100%">
	<!-- Custom Fields -->
	<tr>
		<td colspan="2" align="center">&nbsp;</td>
	</tr>
	{foreach from=$custom_fields item=f key=f_id}
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				{if $bulk}
				<label><input type="checkbox" onclick="toggleDiv('bulkOpts{$f_id}');" name="field_ids[]" value="{$f_id}"><span style="font-size:90%;">{$f->name}:</span></label>
				{else}
					<span style="font-size:0.9em;">
					<input type="hidden" name="field_ids[]" value="{$f_id}">
					{if $f->type=='U'}
						{if !empty($custom_field_values.$f_id)}<a href="{$custom_field_values.$f_id|escape}" target="_blank">{$f->name}</a>{else}{$f->name}{/if}:
					{else}
						{$f->name}:
					{/if}
					</span>
				{/if}
			</td>
			<td width="99%">
				<div id="bulkOpts{$f_id}" style="display:{if $bulk}none{else}block{/if};">
				{if $f->type=='S'}
					<input type="text" name="field_{$f_id}" size="45" style="width:98%;" maxlength="255" value="{$custom_field_values.$f_id|escape}">
				{elseif $f->type=='U'}
					<input type="text" name="field_{$f_id}" size="45" style="width:98%;" maxlength="255" value="{$custom_field_values.$f_id|escape}" class="url">
				{elseif $f->type=='N'}
					<input type="text" name="field_{$f_id}" size="45" style="width:98%;" maxlength="255" value="{$custom_field_values.$f_id|escape}" class="number">
				{elseif $f->type=='T'}
					<textarea name="field_{$f_id}" rows="4" cols="50" style="width:98%;">{$custom_field_values.$f_id|escape}</textarea>
				{elseif $f->type=='C'}
					<label><input type="checkbox" name="field_{$f_id}" value="1" {if $custom_field_values.$f_id}checked="checked"{/if}> {$translate->_('common.yes')|capitalize}</label>
				{elseif $f->type=='X'}
					{if $bulk}
						{foreach from=$f->options item=opt}
							<select name="field_{$f_id}[]">
								<option value=""></option>
								<option value="+{$opt|escape}">set</option>
								<option value="-{$opt|escape}">unset</option>
							</select>
							{$opt}
							<br>
						{/foreach}
					{else}
						{foreach from=$f->options item=opt}
						<label><input type="checkbox" name="field_{$f_id}[]" value="{$opt|escape}" {if isset($custom_field_values.$f_id.$opt)}checked="checked"{/if}> {$opt}</label><br>
						{/foreach}
					{/if}
				{elseif $f->type=='D'}
					<select name="field_{$f_id}">
						<option value=""></option>
						{foreach from=$f->options item=opt}
						<option value="{$opt|escape}" {if $opt==$custom_field_values.$f_id}selected="selected"{/if}>{$opt}</option>
						{/foreach}
					</select>
				{elseif $f->type=='M'}
					{if $bulk}
						{foreach from=$f->options item=opt}
							<select name="field_{$f_id}[]">
								<option value=""></option>
								<option value="+{$opt|escape}">set</option>
								<option value="-{$opt|escape}">unset</option>
							</select>
							{$opt}
							<br>
						{/foreach}
					{else}
						<select name="field_{$f_id}[]" size="5" multiple="multiple">
							{foreach from=$f->options item=opt}
							<option value="{$opt|escape}" {if isset($custom_field_values.$f_id.$opt)}selected="selected"{/if}>{$opt}</option>
							{/foreach}
						</select><br>
						<i><small>{$translate->_('common.tips.multi_select')}</small></i>
					{/if}
				{elseif $f->type=='W'}
					{if empty($workers)}
						{$workers = DAO_Worker::getAllActive()}
					{/if}
					<select name="field_{$f_id}">
						<option value=""></option>
						{foreach from=$workers item=worker}
						<option value="{$worker->id}" {if $worker->id==$custom_field_values.$f_id}selected="selected"{/if}>{$worker->getName()}</option>
						{/foreach}
					</select>
				{elseif $f->type=='F'}
					<input type="file" name="field_{$f_id}" size="45" maxlength="255" value="{$custom_field_values.$f_id|escape}">
				{elseif $f->type=='E'}
					<div id="dateCustom{$f_id}"></div>
					<input type="text" id="field_{$f_id}" name="field_{$f_id}" size="30" maxlength="255" value="{if !empty($custom_field_values.$f_id)}{$custom_field_values.$f_id|devblocks_date}{/if}"><button type="button" onclick="devblocksAjaxDateChooser('#field_{$f_id}','#dateCustom{$f_id}');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
				{/if}
				</div>
			</td>
		</tr>
	{/foreach}
</table>
