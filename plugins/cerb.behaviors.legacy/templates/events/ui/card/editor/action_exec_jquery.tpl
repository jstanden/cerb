<b>jQuery Script:</b>
{$uniqid = uniqid('jqueryScriptEditor')}
<div>
<textarea id="{$uniqid}" name="{$namePrefix}[jquery_script]" data-editor-lines="20" spellcheck="false">{if !empty($params.jquery_script)}{$params.jquery_script}{else}
{if !empty($default_jquery)}{$default_jquery}{else}/*
Use $popup to access the card editor's contents.

$popup.find('...')
*/

// Enter your jQuery script here

{/if}{/if}</textarea>
</div>
<script nonce="{DevblocksPlatform::getRequestNonce()}">
new CerbUI.ScriptingEditor(document.getElementById('{$uniqid}'), { minLines: 3, maxLines: 20 });
</script>
