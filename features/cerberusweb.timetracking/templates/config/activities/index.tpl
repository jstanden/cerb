<h2>{'timetracking.activity.tab'|devblocks_translate}</h2>

<form>
<button type="button" onclick="genericAjaxGet('configActivity','c=config&a=handleSectionAction&section=timetracking&action=getActivity&id=0');"><span class="cerb-sprite2 sprite-plus-circle"></span> {'timetracking.ui.cfg.add_new_activity'|devblocks_translate|capitalize}</button>
</form>

<table cellpadding="0" cellspacing="5" border="0" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">
		
			<fieldset>
				<legend>{'timetracking.ui.cfg.activities'|devblocks_translate}</legend>
				
				<table cellpadding="2" cellspacing="0" border="0">
					<tr>
						<td>
							{foreach from=$activities item=activity}
							<a href="javascript:;" onclick="genericAjaxGet('configActivity','c=config&a=handleSectionAction&section=timetracking&action=getActivity&id={$activity->id}');">{$activity->name}</a><br>
							{/foreach}
						</td>
					</tr>
				</table>
			</fieldset>
			
		</td>
		
		<td width="100%" valign="top">
			<form action="{devblocks_url}{/devblocks_url}" method="post" id="configActivity">
				{include file="devblocks:cerberusweb.timetracking::config/activities/edit_activity.tpl" activity=null}
			</form>
		</td>
		
	</tr>
</table>
