<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPanel('c=config&a=showPreParserPanel&id=0',this,false,'550px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/funnel.gif{/devblocks_url}" align="top"> Add Pre-Parser Filter</button>
</form>

{if !empty($filters)}
<div class="block" id="configMailPreparseFilters">
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveTabPreParseFilters">
<h2>Pre-Parser Filters</h2>

<table cellspacing="2" cellpadding="0">
	{counter start=0 print=false}
	{foreach from=$filters item=filter key=filter_id name=filters}
		<tr>
			<td>
				<input type="checkbox" name="deletes[]" value="{$filter_id}"> 
				<input type="hidden" name="ids[]" value="{$filter_id}">
				<a href="javascript:;" onclick="genericAjaxPanel('c=config&a=showPreParserPanel&id={$filter_id}',this,false,'550px');" style="color:rgb(0,120,0);font-weight:bold;">{$filter->name|escape}</a><br>
				<blockquote style="margin:2px;margin-left:20px;">
					{foreach from=$filter->criteria item=crit key=crit_key}
						{if $crit_key=='type'}
							Is a <b>{$crit.value}</b> message<br>
						{elseif $crit_key=='from'}
							From = <b>{$crit.value}</b><br>
						{elseif $crit_key=='to'}
							{assign var=to_group_id value=$crit.value}
							To = <b>{$groups.$to_group_id->name}</b><br>
						{elseif $crit_key=='header1'}
							Header <i>{$crit.header}</i> = <b>{$crit.value}</b><br>
						{elseif $crit_key=='header2'}
							Header <i>{$crit.header}</i> = <b>{$crit.value}</b><br>
						{elseif $crit_key=='header3'}
							Header <i>{$crit.header}</i> = <b>{$crit.value}</b><br>
						{elseif $crit_key=='header4'}
							Header <i>{$crit.header}</i> = <b>{$crit.value}</b><br>
						{elseif $crit_key=='header5'}
							Header <i>{$crit.header}</i> = <b>{$crit.value}</b><br>
						{elseif $crit_key=='body'}
							Body = <b>{$crit.value}</b><br>
						{elseif $crit_key=='body_encoding'}
							Body Charset = <b>{$crit.value}</b><br>
						{elseif $crit_key=='attachment'}
							Attachment = <b>{$crit.value}</b><br>
						{/if}
					{/foreach}
					
					<blockquote style="margin:2px;margin-left:30px;font-size:90%;color:rgb(130,130,130);">
					{foreach from=$filter->actions item=action key=action_key}
						{if $action_key=="blackhole"}
							Blackhole<br>
						{elseif $action_key=="redirect"}
							Redirect to <b>{$action.to}</b><br>
						{elseif $action_key=="bounce"}
							Bounce<br>
						{/if}
					{/foreach}
					<span>(Matched {$filter->pos} incoming messages)</span><br>
					
					</blockquote>
				</blockquote>
			</td>
		</tr>
	{/foreach}
</table>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> Delete Selected</button>

</form>
</div>
<br>
{/if} {* endif filters exist *}
