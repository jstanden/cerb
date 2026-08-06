<div class="cerb-ui-header cerb-ui-header--tight">
	<div class="cerb-ui-header--title-sm">Sections</div>
</div>

<div class="cerb-u-flex cerb-u-flex-wrap cerb-u-gap-1">
    {if $sections}
        {foreach from=$sections item=section}
			<div class="block cerb-u-px-3 cerb-u-py-2 cerb-u-flex cerb-u-items-center cerb-u-gap-2">
				<h3 class="cerb-u-cursor-pointer" style="text-decoration:underline;{if $section->is_disabled}opacity:0.5;{/if}" data-context="cerb.contexts.toolbar.section" data-context-id="{$section->id}" data-edit="true">{$section->name}</h3>
				{if $section->is_disabled}<span class="cerb-ui-pill cerb-ui-pill--red cerb-u-fs-n2 cerb-u-text-uppercase">{'common.disabled'|devblocks_translate|lower}</span>{/if}
			</div>
        {/foreach}
    {/if}

	<div class="block cerb-u-px-3 cerb-u-py-2">
		<h3 class="cerb-u-cursor-pointer" data-context="cerb.contexts.toolbar.section" data-context-id="0" data-edit="toolbar:{$toolbar_name}"><span class="cerb-icons cerb-icon-circle-plus"></span></h3>
	</div>
</div>

{$script_uid = uniqid('script')}

<script nonce="{DevblocksPlatform::getRequestNonce()}" id="{$script_uid}" type="text/javascript">
$(function() {
    let $script = $('#{$script_uid}');
    let $sections = $script.closest('[data-cerb-toolbar-sections]');

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
