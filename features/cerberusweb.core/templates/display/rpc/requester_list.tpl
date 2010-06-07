{foreach from=$requesters item=req_addy name=reqs}
<div class="ui-corner-all bubble"><a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&address_id={$req_addy->id|escape:'url'}',null,false,'500');">{$req_name=$req_addy->getName()}{if !empty($req_name)}{$req_name|escape} {/if}&lt;{$req_addy->email}&gt;</a></div>
{foreachelse}
{$translate->_('common.nobody')}
{/foreach}
