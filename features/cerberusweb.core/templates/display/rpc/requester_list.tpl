<ul class="bubbles">
{foreach from=$requesters item=req_addy name=reqs}
<li><a href="javascript:;" onclick="genericAjaxPopup('peek','c=contacts&a=showAddressPeek&address_id={$req_addy->id}',null,false,'500');">{$req_name=$req_addy->getName()}{if !empty($req_name)}{$req_name} {/if}&lt;{$req_addy->email}&gt;</a></li>
{foreachelse}
{$translate->_('common.nobody')}
{/foreach}
</ul>
