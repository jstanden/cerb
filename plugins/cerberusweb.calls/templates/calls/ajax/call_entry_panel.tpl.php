<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmCallEntry">
<input type="hidden" name="c" value="calls.ajax">
<input type="hidden" name="a" value="saveEntry">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">

<h1 style="color:rgb(0,150,0);">{$translate->_('calls.ui.log_call')}</h1>

<table cellspacing="0" cellpadding="2" border="0" width="98%">
	<tr>
		<td width="1%" nowrap="nowrap"><b>Contact E-mail:</b></td>
		<td width="99%">
			<div id="emailautocomplete" style="width:98%;margin-bottom:5px;" class="yui-ac">
				<input type="text" name="contact_email" id="emailinput" value="{$model->contact_email|escape}" class="yui-ac-input" autocomplete="off">
				<div id="emailcontainer"></div>
				<br>
			</div>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<table cellspacing="2" cellpadding="2" border="0" width="98%" style="padding:5px;border:1px solid rgb(200,200,200);">
				<tr>
					<td>First Name:</td>
					<td><input type="text" name="contact_firstname" size="45" maxlength="255" style="width:98%;" value="{$model->contact_firstname|escape}"></td>
				</tr>
				<tr>
					<td>Last Name:</td>
					<td><input type="text" name="contact_lastname" size="45" maxlength="255" style="width:98%;" value="{$model->contact_lastname|escape}"></td>
				</tr>
				<tr>
					<td>Phone:</td>
					<td><input type="text" name="contact_phone" size="45" maxlength="255" style="width:98%;" value="{$model->contact_phone|escape}"></td>
				</tr>
				<tr>
					<td>Organization:</td>
					<td>
						<div id="contactautocomplete" style="width:98%;margin-bottom:5px;" class="yui-ac">
							<input type="text" name="org" id="contactinput" value="{$org->name|escape}" class="yui-ac-input">
							<div id="contactcontainer" class="yui-ac-container"></div>
							<br>
						</div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<b>Comment:</b><br>
			<textarea name="comment" rows="5" cols="45" style="width:98%;"></textarea>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<b>Worker:</b><br>
			<select name="worker_id">
				<option value="0">&nbsp;</option>
				{foreach from=$workers item=worker key=worker_id name=workers}
				{if $worker_id==$active_worker->id}{assign var=active_worker_sel_id value=$smarty.foreach.workers.iteration}{/if}
				<option value="{$worker_id}" {if $worker_id==$model->worker_id}selected{/if}>{$worker->getName()}</option>
				{/foreach}
			</select>{if !empty($active_worker_sel_id)}<button type="button" onclick="this.form.worker_id.selectedIndex = {$active_worker_sel_id};">me</button>{/if}
		</td>
	</tr>
</table>
<br>

<button type="button" onclick="genericAjaxPost('frmCallEntry','','c=calls.ajax&a=saveEntry');genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
{if $model->id && ($active_worker->is_superuser || $active_worker->id == $model->worker_id)}<button type="button" onclick="if(confirm('Permanently delete this call entry?')){literal}{{/literal}this.form.do_delete.value='1';genericAjaxPost('frmCallEntry','','c=calls.ajax&a=saveEntry');genericPanel.hide();{literal}}{/literal}"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_delete.gif{/devblocks_url}" align="top"> {$translate->_('common.delete')|capitalize}</button>{/if}
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
</form>