<form>
	{* [WGM]: Please respect our licensing and support the project! *}
	{if (empty($license.workers)&&count($workers)>=3)||(!empty($license.workers)&&$license.workers<50&&count($workers)>=$license.workers)}
	<div class="ui-widget">
		<div class="ui-state-error ui-corner-all" style="padding: 0.7em; margin: 0.2em; "> 
			<p>
				<span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>
				<strong>You have reached the limit of {if !empty($license.workers)}({$license.workers}){else}(3){/if} workers permitted by your license.</strong><br>
				{if (!empty($license.workers)&&count($workers)>$license.workers) || (empty($license.workers)&&count($workers)>3)}<strong>You are licensed for {if !empty($license.workers)}({$license.workers}){else}(3){/if} workers but have ({count($workers)}). Please be honest.</strong><br>{/if}
				<a href="{devblocks_url}c=config&a=settings{/devblocks_url}">(upgrade license)</a>
			</p>
		</div>
	</div>
	{else}
	<button type="button" onclick="genericAjaxPanel('c=config&a=showWorkerPeek&id=0&view_id={$view->id|escape:'url'}',null,false,'500');"><span class="cerb-sprite sprite-add"></span> Add Worker</button>
	{/if}
</form>

<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td valign="top" width="0%" nowrap="nowrap">
			{include file="file:$core_tpl/internal/views/criteria_list.tpl" divName="workersCriteriaDialog"}
			<div id="workersCriteriaDialog" style="visibility:visible;"></div>
		</td>
		<td valign="top" width="0%" nowrap="nowrap" style="padding-right:5px;"></td>
		<td valign="top" width="100%">
			<div id="view{$view->id}">{$view->render()}</div>
		</td>
	</tr>
</table>
