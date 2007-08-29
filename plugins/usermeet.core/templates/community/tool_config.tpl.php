<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="community">
<input type="hidden" name="a" value="saveConfiguration">
<input type="hidden" name="code" value="{$instance->code}">
<input type="hidden" name="finished" value="0">

<H2>{$tool->manifest->name}</H2>
Community: <b>{$community->name}</b><br>
Profile ID: <b>{$instance->code}</b><br>
<br>

{$tool->configure($instance)}

<br>
<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/disk_blue_ok.gif{/devblocks_url}" align="top"> {'Save & Continue'|capitalize}</button>
<button type="button" onclick="this.form.finished.value='1';this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {'Save & Finish'|capitalize}</button>
<button type="button" onclick="javascript:document.location='{devblocks_url}c=community{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>

