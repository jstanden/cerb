<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="community">
<input type="hidden" name="a" value="saveConfiguration">
<input type="hidden" name="code" value="{$instance->code}">
<input type="hidden" name="finished" value="0">
<input type="hidden" name="do_delete" value="0">

<H2>{$tool->manifest->name}</H2>
Community: <b>{$community->name}</b><br>
Profile ID: <b>{$instance->code}</b><br>
<br>

{if !empty($instance) && !empty($tool)}
{$tool->configure($instance)}
{/if}

<br>
<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/disk_blue_ok.gif{/devblocks_url}" align="top"> {'Save & Continue'|capitalize}</button>
<button type="button" onclick="this.form.finished.value='1';this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {'Save & Finish'|capitalize}</button>
{if !empty($instance)}<button type="button" onclick="{literal}if(confirm('Are you sure you want to permanently delete this community tool?')){this.form.do_delete.value='1';this.form.submit();}{/literal}"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete2.gif{/devblocks_url}" align="top"> {$translate->_('common.delete')|capitalize}</button>{/if}
<button type="button" onclick="javascript:document.location='{devblocks_url}c=community{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>

