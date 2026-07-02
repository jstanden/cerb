{if !$custom_fieldsets_available}
	{$custom_fieldsets_available = DAO_CustomFieldset::getUsableByActorByContext($active_worker, $context)}
{/if}

<div class="custom-fieldset-insertion"></div>

{if !empty($custom_fieldsets_available)}
{$cfset_btn_domid = "cfieldAdd_{uniqid()}"}
{$cfset_menu_domid = "cfieldAddMenu_{uniqid()}"}
<div class="cerb-u-mb-3">
	<button type="button" id="{$cfset_btn_domid}" class="cerb-ui-button"><span class="cerb-icons cerb-icon-circle-plus"></span> Add Fieldset <span class="cerb-icons cerb-icon-chevron-down"></span></button>
	<ul id="{$cfset_menu_domid}" hidden>
		{foreach from=$custom_fieldsets_available item=cf_group}
			{$cf_owner = $cf_group->getOwnerDictionary()}
			{* Eyebrow = owner name; fall back to the owner type name when no specific record is pinned
			   (e.g. an application- or workflow-owned fieldset with owner id 0) *}
			{$cf_owner_label = $cf_owner->_label}
			{if !$cf_owner_label}
				{$cf_owner_mft = Extension_DevblocksContext::get($cf_group->owner_context, false)}
				{if $cf_owner_mft}{$cf_owner_label = $cf_owner_mft->name}{/if}
			{/if}
			<li data-id="{$cf_group->id}" data-owner-label="{$cf_owner_label}" data-owner-image="{$cf_owner->_image_url}" data-owner-seed="{$cf_group->owner_context}:{$cf_group->owner_context_id}">{$cf_group->name}</li>
		{/foreach}
	</ul>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var menuSource = document.getElementById('{$cfset_menu_domid}');
	var $btn = $('#{$cfset_btn_domid}');
	var menu = null;

	var onRenderItem = function(li, src) {
		const ownerLabel = src.getAttribute('data-owner-label') || '';
		const ownerImage = src.getAttribute('data-owner-image') || '';
		const ownerSeed = src.getAttribute('data-owner-seed') || ownerLabel;
		const fieldsetName = (src.textContent || '').trim();

		// Replace the default single-line label with an avatar + eyebrow(owner)/main(fieldset) stack
		const lbl = li.querySelector('.cerb-ui-menu--label');
		if(lbl) lbl.remove();

		if(window.CerbUI && CerbUI.Avatar) {
			const av = CerbUI.Avatar.create({ label: ownerLabel || fieldsetName, seed: 'cfieldset-owner:' + ownerSeed, imageUrl: ownerImage, size: 22 });
			av.classList.add('cerb-ui-menu--avatar');
			li.insertBefore(av, li.firstChild);
		}

		const stack = document.createElement('span');
		stack.className = 'cerb-ui-menu--text';

		if(ownerLabel) {
			const eyebrow = document.createElement('span');
			eyebrow.className = 'cerb-ui-menu--eyebrow';
			eyebrow.textContent = ownerLabel;
			stack.appendChild(eyebrow);
		}

		const main = document.createElement('span');
		main.className = 'cerb-ui-menu--main';
		main.textContent = fieldsetName;
		stack.appendChild(main);

		li.appendChild(stack);
	};

	var onSelect = function(li, src) {
		var cf_group_id = src.getAttribute('data-id');

		genericAjaxGet('', 'c=internal&a=invoke&module=records&action=getCustomFieldSet&bulk=1&id=' + cf_group_id, function(html) {
			if(undefined == html || null == html)
				return;

			var $at = $btn.closest('div').siblings('div.custom-fieldset-insertion');
			var $fieldset = $(html);

			// When this fieldset is removed, re-offer it in the menu
			$fieldset.on('custom_fieldset_delete', function(e) {
				$(menuSource).find('li[data-id="' + e.fieldset_id + '"]').removeAttr('hidden');
				rebuildMenu();
			});

			$fieldset.insertBefore($at);

			// Keyboard: move focus into the newly added fieldset so TAB order continues from it (after its
			// inline scripts enhance any cerb-ui controls)
			setTimeout(function() {
				var focusable = $fieldset.find('input:not([type=hidden]), textarea, select, button, a[href], [tabindex]')
					.filter(':visible')
					.filter(function() { return !this.disabled && this.tabIndex !== -1; })
					.first();
				if(focusable.length) focusable.focus();
			}, 50);
		});

		src.setAttribute('hidden', 'hidden');
		rebuildMenu();
	};

	function rebuildMenu() {
		if(menu && menu.destroy) menu.destroy();
		menu = (window.CerbUI && CerbUI.Menu) ? new CerbUI.Menu(menuSource, { filter: true, panelClass: 'cerb-ui-menu--rich', itemHeight: 40, onRenderItem: onRenderItem, onSelect: onSelect }) : null;
		var anyVisible = menuSource.querySelector('li[data-id]:not([hidden])');
		$btn.closest('div')[anyVisible ? 'show' : 'hide']();
	}

	rebuildMenu();

	$btn.on('click', function() {
		if(menu) menu.isOpen() ? menu.close() : menu.open(this);
	});
});
</script>
{/if}
