{$view_fields = $view->getColumnsAvailable()}
{assign var=results value=$view->getData()}
{assign var=total value=$results[1]}
{assign var=tmpdata value=$results[0]}
{assign var=data value=[]}

{foreach from=$tmpdata item=item name=data}
	{$data.{$item.j_context_id} = $item}
{/foreach}

<ul style="list-style-type: none;">
	{* Column Data *}
	{foreach from=$data item=result key=idx name=results}
		<li style="background: url('{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/traffic_light_small_{$result.j_state}.png{/devblocks_url}') no-repeat; padding: 0 0 0 30px;">
			{$server = DAO_Server::get($result.j_context_id)}
			{if null !== $server}
				<a href="{if !empty($url)}{$url}detail/{$result.j_id}-{$result.j_journal|devblocks_permalink}{else}{devblocks_url}c=server&a=detail&id={$result.j_id}-{$result.j_journal|devblocks_permalink}{/devblocks_url}{/if}">{$server->name}</a>
			{/if}
		</li>
	{/foreach}
</ul>

<div class="more">
	<a href="{if !empty($url)}{$url}{else}{devblocks_url}c=server{/devblocks_url}{/if}">{$translate->_('common.more')|capitalize}...</a>
</div>