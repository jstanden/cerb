<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="1%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/gear.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="100%"><h1>Bulk Update</h1></td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate">
<input type="hidden" name="c" value="crm">
<input type="hidden" name="a" value="doOppBulkUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="opp_ids" value="{$opp_ids}">
<div style="height:400px;overflow:auto;">

<h2>With:</h2>

<label><input type="radio" name="filter" value="" {if empty($opp_ids)}checked{/if}> Whole list</label> 
<label><input type="radio" name="filter" value="checks" {if !empty($opp_ids)}checked{/if}> Only checked</label> 
<br>
<br>

<div id="bulkUpdateCustom" style="display:block;">
<H2>Do:</H2>
<table cellspacing="0" cellpadding="2" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right">Status:</td>
		<td width="100%"><select name="status">
			<option value=""></option>
			<option value="open">open</option>
			<option value="won">closed/won</option>
			<option value="lost">closed/lost</option>
      	</select>
		<button type="button" onclick="this.form.status.selectedIndex = 1;">open</button>
		<button type="button" onclick="this.form.status.selectedIndex = 2;">won</button>
		<button type="button" onclick="this.form.status.selectedIndex = 3;">lost</button>
      	</td>
	</tr>
	
	<tr>
		<td width="0%" nowrap="nowrap">Worker:</td>
		<td width="100%"><select name="worker_id">
			<option value=""></option>
			<option value="0">Anybody</option>
			{foreach from=$workers item=worker key=worker_id name=workers}
				{if $worker_id==$active_worker->id}{math assign=me_worker_id equation="x+1" x=$smarty.foreach.workers.iteration}{/if}
				<option value="{$worker_id}">{$worker->getName()}</option>
			{/foreach}
		</select>
      	{if !empty($me_worker_id)}
      		<button type="button" onclick="this.form.worker_id.selectedIndex = {$me_worker_id};">me</button>
      		<button type="button" onclick="this.form.worker_id.selectedIndex = 1;">anybody</button>
      	{/if}
		</td>
	</tr>
	
</table>
	
<table cellspacing="0" cellpadding="2" width="100%">
	<!-- Custom Fields -->
	<tr>
		<td colspan="2" align="center">&nbsp;</td>
	</tr>
	{foreach from=$custom_fields item=f key=f_id}
		<tr>
			<td width="1%" nowrap="nowrap">
				<label><input type="checkbox" name="field_ids[]" value="{$f_id}"><span style="font-size:90%;">{$f->name}:</span></label>
			</td>
			<td width="99%">
				{if $f->type=='S'}
					<input type="text" name="field_{$f_id}" size="45" maxlength="255" value=""><br>
				{elseif $f->type=='T'}
					<textarea name="field_{$f_id}" rows="4" cols="50" style="width:98%;"></textarea><br>
				{elseif $f->type=='C'}
					<input type="checkbox" name="field_{$f_id}" value="1"><br>
				{elseif $f->type=='D'}
					<select name="field_{$f_id}">
						<option value=""></option>
						{foreach from=$f->options item=opt}
						<option value="{$opt|escape}">{$opt}</option>
						{/foreach}
					</select><br>
				{elseif $f->type=='E'}
					<input type="text" name="field_{$f_id}" size="30" maxlength="255" value=""><button type="button" onclick="ajax.getDateChooser('dateCustom{$f_id}',this.form.field_{$f_id});">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
					<div id="dateCustom{$f_id}" style="display:none;position:absolute;z-index:1;"></div>
				{/if}	
			</td>
		</tr>
	{/foreach}
		
</table>

<br>
</div>

<button type="button" onclick="genericPanel.hide();genericAjaxPost('formBatchUpdate','view{$view_id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
<br>
</form>