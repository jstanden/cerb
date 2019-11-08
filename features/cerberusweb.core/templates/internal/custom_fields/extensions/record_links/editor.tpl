{$field_uniqid = uniqid('cfield_')}
<div id="{$field_uniqid}">
    <button type="button" class="chooser-cfield-links" data-field-name="{$form_key}[]" data-context="{$field->params.context}" data-query="" data-autocomplete="" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>

    <ul class="bubbles chooser-container">
        {if $linked_dicts && is_array($linked_dicts)}
            {foreach from=$linked_dicts item=linked_dict}
            <li>
                <a href="javascript:;" class="peek-cfield-links no-underline" data-context="{$linked_dict->_context}" data-context-id="{$linked_dict->id}">{$linked_dict->_label}</a>
                <input type="hidden" name="{$form_key}[]" value="{$linked_dict->id}">
            </li>
            {/foreach}
        {/if}
    </ul>
</div>

<script type="text/javascript">
$(function() {
	var $cfield = $('#{$field_uniqid}');

	// Links
	$cfield.find('button.chooser-cfield-links').cerbChooserTrigger();
	$cfield.find('a.peek-cfield-links').cerbPeekTrigger();
});
</script>