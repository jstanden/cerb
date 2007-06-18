<div id="teamFilters">
{if !empty($categories)}
<div class="block">
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="saveTeamFilters">
<input type="hidden" name="team_id" value="{$dashboard_team_id}">
<table cellpadding="0" cellspacing="0" border="0" width="220">
	<tr>
		<td nowrap="nowrap"> <h2>{$translate->_('dashboard.team_filters')|capitalize}</h2></td>
	</tr>
	<tr>
		<td width="100%">
			<label><input type="radio" name="categorized" value="0" {if !$team_filters.categorized}checked{/if} onclick="toggleDiv('teamCategories','none');"> <b>All active</b></label><br>
			<label><input type="radio" name="categorized" value="1" {if $team_filters.categorized}checked{/if} onclick="toggleDiv('teamCategories','block');"> <b>In these buckets:</b></label>
			<a href="javascript:;" onclick="checkAll('teamCategories',false);">clear</a> 
			<br>
			
			<div id="teamCategories" style="display:{if !$team_filters.categorized}none{else}block{/if};">
			<label><input type="checkbox" name="categories[]" value="0" {if isset($team_filters.categories.0)}checked{/if} onclick="this.form.categorized[1].checked=true;"> Inbox ({if isset($category_counts.0)}{$category_counts.0}{else}0{/if})</label><br>
			<blockquote style="margin:0px;margin-left:5px;margin-bottom:10px;">
			<table cellpadding="0" cellspacing="0" border="0" width="100%">
				{foreach from=$categories item=category key=category_id}
					{if $category_counts.total && $category_counts.$category_id}
						{math assign=percent equation="(x/y)*50" x=$category_counts.$category_id y=$category_counts.total format="%0.0f"}
					{/if}
				<tr>
					<td width="100%"><label><input type="checkbox" name="categories[]" value="{$category->id}" {if isset($team_filters.categories.$category_id)}checked{/if} onclick="this.form.categorized[1].checked=true;"> {$category->name} ({if isset($category_counts.$category_id)}{$category_counts.$category_id}{else}0{/if})</label></td>
					<td width="0%" nowrap="nowrap" style="width:51px;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/cerb_graph.gif{/devblocks_url}" width="{$percent}" height="15"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/cer_graph_cap.gif{/devblocks_url}" height="15" width="1"></td>
				</tr>
				{/foreach}
			</table>
			</blockquote>
			</div>
			
			<!-- <label><input type="checkbox" name="show_waiting" value="1" {if $team_filters.show_waiting}checked{/if}> Show Waiting Tickets</label><br>  -->
			<!-- <label><input type="checkbox" name="hide_assigned" value="1" {if $team_filters.hide_assigned}checked{/if}> Hide with Active Tasks</label><br>  -->
			
			<div align="right">
				<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/funnel.gif{/devblocks_url}" align="top"> {$translate->_('common.filter')|capitalize}</button>
			</div>
		</td>
	</tr>
</table>
</form>
</div>
{/if}
</div>