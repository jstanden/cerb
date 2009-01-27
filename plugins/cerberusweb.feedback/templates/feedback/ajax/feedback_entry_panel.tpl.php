<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmFeedbackEntry">
<input type="hidden" name="c" value="feedback">
<input type="hidden" name="a" value="saveEntry">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">

<h1>Capture Feedback</h1>

<div style="height:350px;overflow:auto;margin:2px;padding:3px;">

<b>Author E-mail:</b> (optional; blank for anonymous)<br>
<input type="text" name="email" size="45" maxlength="255" style="width:98%;" value="{$address->email|escape}"><br>
<br>

<b>Quote:</b><br>
<textarea name="quote" cols="45" rows="4" style="width:98%;">{$model->quote_text|escape}</textarea><br>
<br>

<b>Mood:</b> 
<label><input type="radio" name="mood" value="1" {if 1==$model->quote_mood}checked{/if}> <span style="background-color:rgb(235, 255, 235);color:rgb(0, 180, 0);font-weight:bold;">Praise</span></label>
<label><input type="radio" name="mood" value="0" {if empty($model->quote_mood)}checked{/if}> Neutral</label>
<label><input type="radio" name="mood" value="2" {if 2==$model->quote_mood}checked{/if}> <span style="background-color: rgb(255, 235, 235);color: rgb(180, 0, 0);font-weight:bold;">Criticism</span></label>
<br>
<br>

<b>Save to List:</b><br>
<select name="list_id">
	<option value="0">- inbox -</option>
	{if !empty($lists)}
	{foreach from=$lists item=list}
	<option value="{$list->id}" {if $list->id == $model->list_id} selected{/if}>{$list->name}</option>
	{/foreach}
	{/if}
</select>
<br>
<br>

<b>Source URL:</b> (optional)<br>
<input type="text" name="url" size="45" maxlength="255" style="width:98%;" value="{$model->source_url|escape}"><br>
<br>

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<!-- Custom Fields -->
	<tr>
		<td colspan="2" align="center">&nbsp;</td>
	</tr>
	{foreach from=$feedback_fields item=f key=f_id}
		<tr>
			<td valign="top" width="0%" nowrap="nowrap" align="right">
				<input type="hidden" name="field_ids[]" value="{$f_id}">
				<span style="font-size:90%;">{$f->name}:</span>
			</td>
			<td valign="top" width="100%">
				{if $f->type=='S'}
					<input type="text" name="field_{$f_id}" size="45" maxlength="255" value="{$feedback_field_values.$f_id}"><br>
				{elseif $f->type=='N'}
					<input type="text" name="field_{$f_id}" size="45" maxlength="255" value="{$feedback_field_values.$f_id}"><br>
				{elseif $f->type=='T'}
					<textarea name="field_{$f_id}" rows="4" cols="50" style="width:98%;">{$feedback_field_values.$f_id}</textarea><br>
				{elseif $f->type=='C'}
					<input type="checkbox" name="field_{$f_id}" value="1" {if $feedback_field_values.$f_id}checked{/if}><br>
				{elseif $f->type=='D'}
					<select name="field_{$f_id}">{* [TODO] Fix selected *}
						<option value=""></option>
						{foreach from=$f->options item=opt}
						<option value="{$opt|escape}" {if $opt==$feedback_field_values.$f_id}selected{/if}>{$opt}</option>
						{/foreach}
					</select><br>
				{elseif $f->type=='E'}
					<input type="text" name="field_{$f_id}" size="30" maxlength="255" value="{if !empty($feedback_field_values.$f_id)}{$feedback_field_values.$f_id|devblocks_date}{/if}"><button type="button" onclick="ajax.getDateChooser('dateCustom{$f_id}',this.form.field_{$f_id});">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
					<div id="dateCustom{$f_id}" style="display:none;position:absolute;z-index:1;"></div>
				{/if}	
			</td>
		</tr>
	{/foreach}
</table>

<input type="hidden" name="source_extension_id" value="{$source_extension_id}">
<input type="hidden" name="source_id" value="{$source_id}">

</div>
<br>

{if empty($model->id)}
<button type="button" onclick="genericAjaxPost('frmFeedbackEntry','','c=feedback&a=saveEntry');genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
{else}
<button type="button" onclick="genericAjaxPost('frmFeedbackEntry','','c=feedback&a=saveEntry');genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
{if $active_worker->is_superuser || $active_worker->id == $model->worker_id}<button type="button" onclick="if(confirm('Permanently delete this feedback?')){literal}{{/literal}this.form.do_delete.value='1';genericAjaxPost('frmFeedbackEntry','','c=feedback&a=saveEntry');genericPanel.hide();{literal}}{/literal}"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_delete.gif{/devblocks_url}" align="top"> {$translate->_('common.delete')|capitalize}</button>{/if}
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
{/if}
</form>