<b>Authenticate logins using these methods:</b>
<ul style="margin:0px;padding:0px;list-style:none;">
{foreach from=$login_extensions item=ext}
	<li><label><input type="checkbox" name="login_extensions[]" value="{$ext->id}" {if isset($login_extensions_enabled.{$ext->id})}checked="checked"{/if}> {$ext->name}</label></li>
{/foreach} 
</ul>
<br>
