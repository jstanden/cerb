<b>jQuery Script:</b>
<div>
<textarea name="{$namePrefix}[jquery_script]" rows="3" cols="45" style="width:100%;" class="placeholders" data-editor-mode="ace/mode/twig_javascript" wrap="off" spellcheck="false">{if !empty($params.jquery_script)}{$params.jquery_script}{else}
{if !empty($default_jquery)}{$default_jquery}{else}/*
Use $popup to access the card editor's contents.

$popup.find('...')
*/

// Enter your jQuery script here

{/if}{/if}</textarea>
</div>
