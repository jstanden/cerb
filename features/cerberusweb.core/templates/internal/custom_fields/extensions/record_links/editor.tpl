{$field_uniqid = uniqid('cfield_')}
{* Self-contained record-links chooser. No `data-cerb-cfield-chooser` marker so the parent form's generic
   chooser init loop doesn't also claim it (which would re-init it single, dropping multiple). It's a direct
   child of the field cell so it flex-stretches to full width like the other cfield types. *}
<div class="cerb-ui-record-chooser" id="{$field_uniqid}" data-context="{$field->params.context}" data-name="{$form_key}">
    {if $linked_dicts && is_array($linked_dicts)}
        {foreach from=$linked_dicts item=linked_dict}
        <li data-context-id="{$linked_dict->id}" data-label="{$linked_dict->_label}" data-image="{$linked_dict->_image_url}"></li>
        {/foreach}
    {/if}
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var el = document.getElementById('{$field_uniqid}');

	if(el && window.CerbUI && CerbUI.RecordChooser)
		new CerbUI.RecordChooser(el, {
			context: el.getAttribute('data-context'),
			name: el.getAttribute('data-name'),
			emptyIcon: 'link',
			multiple: true
		});
});
</script>