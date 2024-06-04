<legend>Sections:</legend>

<div style="display:flex;flex-flow:row wrap;gap:5px;">
    {if $sections}
        {foreach from=$sections item=section}
			<div class="block" style="display:inline-block;padding:0.5em 1em;">
				<h3 style="text-decoration:underline;cursor:pointer;{if $section->is_disabled}opacity:0.5;{/if}" data-context="cerb.contexts.toolbar.section" data-context-id="{$section->id}" data-edit="true">{$section->name}</h3>
			</div>
        {/foreach}
    {/if}
	
	<div class="block" style="display:inline-block;padding:0.5em 1em;">
		<h3 style="text-decoration:none;cursor:pointer;{if $section->is_disabled}opacity:0.5;{/if}" data-context="cerb.contexts.toolbar.section" data-context-id="0" data-edit="toolbar:{$toolbar_name}"><span class="glyphicons glyphicons-plus"></span></h3>
	</div>
</div>

{$script_uid = uniqid('script')}

<script nonce="{DevblocksPlatform::getRequestNonce()}" id="{$script_uid}" type="text/javascript">
$(function() {
    let $script = $('#{$script_uid}');
    let $sections = $script.closest('fieldset');
    
    $sections.find('[data-context]')
        .cerbPeekTrigger({
            'width': '80%'
        })
        .on('cerb-peek-saved cerb-peek-deleted', function(e) {
            e.stopPropagation();

            var formData = new FormData();
            formData.set('c', 'profiles');
            formData.set('a', 'invoke');
            formData.set('module', 'toolbar');
            formData.set('action', 'refreshSections');
            formData.set('toolbar_id', '{$toolbar_id}');

            genericAjaxPost(formData, $sections);
        })
    ;
});
</script>