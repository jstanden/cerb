<div class="block">
<form action="{devblocks_url}{/devblocks_url}" method="post" id="formConfigCommunityTool">
<input type="hidden" name="c" value="community">
<input type="hidden" name="a" value="saveTabSettings">
<input type="hidden" name="portal" value="{$instance->code}">
<input type="hidden" name="do_delete" value="0">

<H2>Settings</H2>

<b>Portal Name:</b> ("Support Portal", "Contact Form", "ProductX FAQ")<br>
<input type="text" name="portal_name" value="{if !empty($instance->name)}{$instance->name}{else}{$instance->manifest->name}{/if}" size="65"><br>
<br> 

{if !empty($instance) && !empty($tool)}
{$tool->configure($instance)}
{/if}

<br>

<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
{if !empty($instance)}<button type="button" onclick="if(confirm('{$translate->_('usermeet.ui.community.cfg.confirm_delete')}')){literal}{this.form.do_delete.value='1';this.form.submit();}{/literal}"><span class="cerb-sprite sprite-delete2"></span> {$translate->_('common.delete')|capitalize}</button>{/if}

</form>
</div>
<br>