{$custom_fieldsets_all = DAO_CustomFieldset::getByContext($context)}

{if $custom_fieldsets_linked}
	{foreach from=$custom_fieldsets_linked item=cf_group}
	{* custom_field_values here are the action's saved form strings, not DB-scaled ints *}
	{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/fieldset.tpl" custom_fieldset=$cf_group field_wrapper=$field_wrapper custom_field_values_raw=true}
	{/foreach}
{/if}

<div class="custom-fieldset-insertion"></div>

{$btn_cfield_group_domid = "cfield_sets_{uniqid()}"}
<div class="cerb-u-my-2">
	<button id="{$btn_cfield_group_domid}" type="button" class="cerb-ui-button cerb-ui-button--subtle"><span class="cerb-icons cerb-icon-circle-plus"></span> Add Fieldset <span class="cerb-icons cerb-icon-chevron-down"></span></button>

	<ul class="cerb-cfieldset-menu" style="width:250px;display:none;">
		{foreach from=$custom_fieldsets_all item=cf_group key=cf_group_id}
		{$owner_ctx = Extension_DevblocksContext::get($cf_group->owner_context|default:'')}
		{$cf_owner_label = ''}
		{if $cf_group->owner_context != CerberusContexts::CONTEXT_APPLICATION && is_a($owner_ctx, 'Extension_DevblocksContext')}
			{* Fall back to the owner record type when the record has no resolvable name (e.g. workflow-owned) *}
			{$meta = $owner_ctx->getMeta($cf_group->owner_context_id)}
			{$cf_owner_name = $meta.name|default:$owner_ctx->manifest->name}
			{$cf_owner_label = " ({$cf_owner_name})"}
		{/if}
		<li data-cf-group-id="{$cf_group->id}" data-cf-idx="{$cf_group@iteration}"{if $custom_fieldsets_linked.$cf_group_id} data-cf-linked="1"{/if}>
			<div>{$cf_group->name}{$cf_owner_label}</div>
		</li>
		{/foreach}
	</ul>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $button = $('#{$btn_cfield_group_domid}');
	const $menu = $button.siblings('ul.cerb-cfieldset-menu');
	const detached = new Map();
	let menu = null;

	// Custom-field text values accept placeholders at run time (tpl_builder), so tag their carriers
	// for the action editor's placeholder toolbar. DatePicker inputs and hidden chooser carriers are
	// excluded by construction.
	const funcTagPlaceholders = function($scope) {
		$scope.find('input[type=text][name^="{$field_wrapper}[field_"]:not([data-cerb-date-picker]), textarea[name^="{$field_wrapper}[field_"]')
			.addClass('placeholders');
	};

	// CerbUI.Menu snapshots its source UL at construction, so availability changes (pick a fieldset,
	// remove one from the form) detach/restore source <li>s and rebuild the menu instance.
	const funcRebuildMenu = function() {
		if(menu) {
			menu.destroy();
			menu = null;
		}

		const hasItems = 0 < $menu.children('li').length;
		$button.toggle(hasItems);

		if(hasItems && window.CerbUI && CerbUI.Menu)
			menu = new CerbUI.Menu($menu[0], { clickTrigger: $button[0], filter: true, onSelect: funcOnSelect });
	};

	const funcDetachOption = function(cf_group_id) {
		const $li = $menu.children('li[data-cf-group-id="' + cf_group_id + '"]');
		if($li.length)
			detached.set(String(cf_group_id), $li.detach());
		funcRebuildMenu();
	};

	const funcRestoreOption = function(cf_group_id) {
		const $li = detached.get(String(cf_group_id));
		if(!$li)
			return;
		detached.delete(String(cf_group_id));

		// Reinsert at the option's original position
		const idx = parseInt($li.attr('data-cf-idx'));
		let placed = false;
		$menu.children('li').each(function() {
			if(parseInt($(this).attr('data-cf-idx')) > idx) {
				$li.insertBefore(this);
				placed = true;
				return false;
			}
		});
		if(!placed)
			$menu.append($li);

		funcRebuildMenu();
	};

	const funcOnSelect = function(li, src) {
		const cf_group_id = src.getAttribute('data-cf-group-id');

		if(!cf_group_id)
			return;

		genericAjaxGet('', 'c=internal&a=invoke&module=records&action=getCustomFieldSet{if $field_wrapper}&field_wrapper={$field_wrapper|escape:'url'}{/if}&id=' + encodeURIComponent(cf_group_id), function(html) {
			if(undefined == html || null == html)
				return;

			const $at = $button.parent().siblings('div.custom-fieldset-insertion');
			const $fieldset = $(html);
			$fieldset.insertBefore($at);

			funcTagPlaceholders($fieldset);
			$fieldset.filter('[data-cerb-custom-fieldset]').trigger('cerb-placeholders--enhance');
		});

		funcDetachOption(cf_group_id);
	};

	// When a fieldset panel is removed from the form, return its option to the menu
	$menu.parent().parent().on('custom_fieldset_delete', '[data-cerb-custom-fieldset]', function(e) {
		funcRestoreOption(e.fieldset_id);
	});

	// Fieldsets already on the form aren't offered again
	$menu.children('li[data-cf-linked]').each(function() {
		detached.set(String($(this).attr('data-cf-group-id')), $(this).detach());
	});

	funcRebuildMenu();

	// Server-rendered fieldsets: tag before the action editor's popup_open enhancement pass runs
	funcTagPlaceholders($button.parent().parent());
});
</script>
