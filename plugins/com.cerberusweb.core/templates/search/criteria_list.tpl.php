<script>
{literal}
	myPanel = null;

	function doCriteria() {
		//myPanel = new YAHOO.widget.Panel("searchCriteriaPanel");
		
		if(null != myPanel) {
			try {
				document.getElementById('searchCriteriaField').selectedIndex = 0;
				document.getElementById('searchCriteriaVal').innerHTML = '';
			} catch(e) {}
			myPanel.show();
			return;
		}
	}
	
	function initPanel() {
		myPanel = new YAHOO.widget.Panel("searchCriteriaPanel", { 
			width:"500px",  
			fixedcenter: true,  
			constraintoviewport: true,  
			underlay:"shadow",  
			close:false,  
			visible:false, 
			modal:true,
			draggable:false} ); 		
			
		myPanel.render();
	}
{/literal}
</script>
<table cellpadding="2" cellspacing="0" width="200" border="0" class="tableGreen">
	<tr>
		<th class="tableThGreen"><img src="images/find.gif"> Search Criteria</th>
	</tr>
	<tr style="border-bottom:1px solid rgb(200,200,200);display:block;">
		<td>
			<a href="javascript:;" onclick="">reset criteria</a> |
			<a href="#">save</a> |
			<a href="#">load</a>
		</td>
	</tr>
	<tr>
		<td style="background-color:rgb(255,255,255);">
			<table cellpadding="2" cellspacing="0" border="0">
				<tr>
					<td colspan="2" align="left">
						<a href="javascript:;" onclick="doCriteria();">Add new criteria</a> 
						<a href="javascript:;" onclick="doCriteria();"><img src="images/data_add.gif" align="absmiddle" border="0"></a> 
					</td>
				</tr>
				{foreach from=$params item=param}
				{if $param->field=="t.status"}
					<tr>
						<td width="100%">
							<img src="images/data_find.gif" align="absmiddle"> 
							{$translate->say('ticket.status')} 
							{$param->operator}
							{foreach from=$param->value item=p name=params}
							<b>{$p}</b>
							 {if !$smarty.foreach.params.last} or {/if}
							{/foreach}
						</td>
						<td width="0%" nowrap="nowrap" valign="top"><a href="#"><img src="images/data_error.gif" border="0" align="absmiddle"></a></td>
					</tr>
				{elseif $param->field=="t.priority"}
					<tr>
						<td width="100%">
							<img src="images/data_find.gif" align="absmiddle"> 
							{$translate->say('ticket.priority')} 
							{$param->operator}
							{foreach from=$param->value item=p name=params}
							<b>{$p}</b>
							 {if !$smarty.foreach.params.last} or {/if}
							{/foreach}
						</td>
						<td width="0%" nowrap="nowrap" valign="top"><a href="#"><img src="images/data_error.gif" border="0" align="absmiddle"></a></td>
					</tr>
				{elseif $param->field=="t.subject"}
					<tr>
						<td width="100%">
							<img src="images/data_find.gif" align="absmiddle"> 
							{$translate->say('ticket.subject')} 
							{$param->operator} 
							<b>{$param->value}</b>
						</td>
						<td width="0%" nowrap="nowrap" valign="top"><a href="#"><img src="images/data_error.gif" border="0" align="absmiddle"></a></td>
					</tr>
				{else}
				{/if}
				{/foreach}
			</table>
		</td>
	</tr>
	<tr>
		<td align="right" style="background-color:rgb(221,221,221);"><input type="button" value="Update Search"></td>
	</tr>
</table>
<div id="searchCriteriaPanel" style="visibility:hidden;">
   <div class="tableThGreen">Add Search Criteria</div>
	<div class="bd">
		<form>
		<input type="hidden" name="c" value="{$c}">
		<input type="hidden" name="a" value="addCriteria">
		<b>Add Criteria:</b>
			<select name='field' id="searchCriteriaField" onchange='ajax.getSearchCriteria(this.options[this.selectedIndex].value)' onkeydown='ajax.getSearchCriteria(this.options[this.selectedIndex].value)'>
				<option value=''>-- select criteria --
				<option value='t.status'>{$translate->say('ticket.status')}
				<option value='t.priority'>{$translate->say('ticket.priority')}
				<option value='t.subject'>{$translate->say('ticket.subject')}
			</select>
			<br>
			<div id='searchCriteriaVal'></div>
		<input type="submit" value="{$translate->say('common.save_changes')|capitalize}"><input type="button" value="{$translate->say('common.cancel')|capitalize}" onclick="myPanel.hide();">
		</form>
	</div>
</div>
<script>
{literal}
YAHOO.util.Event.addListener(window,"load",initPanel);
{/literal}
</script>