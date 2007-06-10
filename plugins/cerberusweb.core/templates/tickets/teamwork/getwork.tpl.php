{if !empty($team_categories)}
<div class="block">
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="">
<input type="hidden" name="team_id" value="{$dashboard_team_id}">
<table cellpadding="0" cellspacing="0" border="0" width="220">
	<tr>
		<td nowrap="nowrap"><h2>Get Tickets</h2></td>
	</tr>
	<tr>
		<td width="100%">
			<button type="button"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/funnel.gif{/devblocks_url}" align="top"> {"Assign"|capitalize}</button>
			<br>
			
			<a href="javascript:;" onclick="toggleDiv('getwork_prefs');">preferences</a>
			
			{* end assign form *}
			
			{* start prefs form *}  
			
			<br>
		
			<div id="getwork_prefs" style="display:none;">
			<br>
			<b>How many?</b><br>
			<input type="text" name="quantity" value="5" size="3" maxlength="2"><br>
			<br>

			{foreach from=$teams item=team key=team_id}
			<b>{$team->name}</b>
			<a href="javascript:;" onclick="checkAll('getwork_team_{$team_id}');">all</a>
			<br>
			<blockquote style="margin:0px;margin-left:5px;">
				<div id="getwork_team_{$team_id}" style="display:block;margin-bottom:5px;">
				<label><input type="checkbox" name="uncategorized[]" value="{$team_id}"> <i>Uncategorized</i></label><br>
				
				{foreach from=$team_categories.$team_id item=category}
					<label><input type="checkbox" name="categories[]" value="{$category->id}" {if isset($x.$x)}checked{/if} onclick=""> {$category->name} {* ({$category_counts.$category_id})*}</label><br>
				{/foreach}
				</div>
			</blockquote>
			{/foreach}
			
			<div align="right">
				<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
			</div>
			
			</div>
			
{*		
					{if $category_counts.0}
						{math assign=percent equation="(x/y)*50" x=$category_counts.$category_id y=$category_counts.0 format="%0.0f"}
					{/if}
				<tr>
					<td width="0%" nowrap="nowrap" style="width:51px;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/cerb_graph.gif{/devblocks_url}" width="{$percent}" height="15"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/cer_graph_cap.gif{/devblocks_url}" height="15" width="1"></td>
				</tr>
*}
		</td>
	</tr>
</table>
</form>
</div>
<br>
{/if}