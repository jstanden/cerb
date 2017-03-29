<div style="margin-bottom:10px;">
	<b>Authenticate logins using these methods:</b>
</div>

{foreach from=$login_extensions item=ext}
<fieldset class="black peek" style="background:none;">
	<legend><label><input type="checkbox" name="login_extensions[]" value="{$ext->id}" {if isset($login_extensions_enabled.{$ext->id})}checked="checked"{/if} onclick="$(this).closest('fieldset').find('> div').toggle();"> {$ext->manifest->name}</label></legend>
	
	<div style="margin-left:25px;{if isset($login_extensions_enabled.{$ext->id})}display:block;{else}display:none;{/if}">
		{$ext->renderConfigForm($instance)}
	</div>
</fieldset>
{/foreach}
