<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmFeedbackEntry">
<input type="hidden" name="c" value="feedback">
<input type="hidden" name="a" value="saveEntry">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">

<h1>Capture Feedback</h1>

<b>Author E-mail:</b> (optional; blank for anonymous)<br>
<input type="text" name="email" size="45" maxlength="255" style="width:98%;" value="{$address->email|escape}"><br>
<br>

<b>Quote:</b><br>
<textarea name="quote" cols="45" rows="4" style="width:98%;">{$model->quote_text|escape}</textarea><br>
<br>

<b>Mood:</b><br>
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

<input type="hidden" name="source_extension_id" value="{$source_extension_id}">
<input type="hidden" name="source_id" value="{$source_id}">

{if empty($model->id)}
<button type="button" onclick="genericAjaxPost('frmFeedbackEntry','','c=feedback&a=saveEntry');genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
{else}
<button type="button" onclick="genericAjaxPost('frmFeedbackEntry','','c=feedback&a=saveEntry');genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
{if $active_worker->is_superuser || $active_worker->id == $model->worker_id}<button type="button" onclick="if(confirm('Permanently delete this feedback?')){literal}{{/literal}this.form.do_delete.value='1';genericAjaxPost('frmFeedbackEntry','','c=feedback&a=saveEntry');genericPanel.hide();{literal}}{/literal}"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete2.gif{/devblocks_url}" align="top"> {$translate->_('common.delete')|capitalize}</button>{/if}
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
{/if}
</form>