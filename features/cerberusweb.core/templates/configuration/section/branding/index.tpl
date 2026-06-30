{$logo_updated_at = $smarty.now}

<div class="cerb-ui-header">
	<div>
		<div class="cerb-ui-header--title">Branding</div>
	</div>
</div>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupBranding" class="cerb-ui-form">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="branding">
<input type="hidden" name="action" value="saveJson">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-u-flex cerb-u-flex-wrap cerb-u-items-stretch cerb-u-gap-3">
	<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-u-flex-1">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">{'common.logo'|devblocks_translate|capitalize} (light)</div>
		</div>

		<div style="margin:5px;background-color:white;border-radius:6px;">
			<img class="img-logo" src="{devblocks_url}c=branding&a=logo{/devblocks_url}?v={$logo_updated_at}" style="max-width:45vw;height:80px;margin:10px;">
		</div>

		<button type="button" class="cerb-ui-button button-file-upload" data-context="resource" data-context-id="ui.logo" data-edit="type:cerb.resource.image description:&quot;The logo displayed in the top left of the UI&quot;" title="{'common.edit'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-edit"></span> {'common.edit'|devblocks_translate|capitalize}</button>
	</div>

	<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-u-flex-1">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">{'common.logo'|devblocks_translate|capitalize} (dark)</div>
		</div>

		<div style="margin:5px;background-color:rgb(32,32,32);border-radius:6px;">
			<img class="img-logo-dark" src="{devblocks_url}c=branding&a=logo-dark{/devblocks_url}?v={$logo_updated_at}" style="max-width:45vw;height:80px;margin:10px;">
		</div>

		<button type="button" class="cerb-ui-button button-file-upload" data-context="resource" data-context-id="ui.logo.dark" data-edit="type:cerb.resource.image description:&quot;The dark variation of the logo displayed in the top left of the UI&quot;" title="{'common.edit'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-edit"></span> {'common.edit'|devblocks_translate|capitalize}</button>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.settings'|devblocks_translate|capitalize}</div>
	</div>

	<div class="cerb-ui-form--row">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Browser Title</label>
			<label class="cerb-ui-form--control">
				<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-window-top"></span>
				<input type="text" name="title" value="{$settings->get('cerberusweb.core','helpdesk_title')}">
			</label>
		</div>
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Favicon URL <span class="cerb-ui-form--hint">(leave blank for default)</span></label>
			<label class="cerb-ui-form--control">
				<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-link"></span>
				<input type="text" name="favicon" value="{$settings->get('cerberusweb.core','helpdesk_favicon_url')}">
			</label>
		</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Custom Stylesheet</div>
	</div>

	<textarea name="user_stylesheet" data-editor-lines="15" spellcheck="false">{$settings->get('cerberusweb.core','ui_user_stylesheet')}</textarea>
</div>

<div>
	<button type="button" id="btnSaveBranding" class="cerb-ui-button cerb-u-anim-group"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $frm = $('#frmSetupBranding');

	Devblocks.formDisableSubmit($frm);

	$frm.find('button.button-file-upload')
		.cerbPeekTrigger()
		.on('cerb-peek-saved cerb-peek-deleted cerb-peek-aborted', function(e) {
			e.stopPropagation();

			const $logo = $('#cerb-logo');
			const $img = $frm.find('img.img-logo');
			const $img_dark = $frm.find('img.img-logo-dark');
			const now = new Date().getTime();

			$img.attr('src', '{devblocks_url}c=branding&a=logo{/devblocks_url}?v=' + now);
			$img_dark.attr('src', '{devblocks_url}c=branding&a=logo-dark{/devblocks_url}?v=' + now);

			{if $pref_dark_mode}
			$logo.css('background-image', 'url(' + $img_dark.attr('src') + ')');
			{else}
			$logo.css('background-image', 'url(' + $img.attr('src') + ')');
			{/if}
		})
	;

	$frm.find('#btnSaveBranding')
		.click(function() {
			Devblocks.saveAjaxForm($frm);
		})
	;

	// Custom stylesheet — a plain ScriptingEditor (no autocomplete; CSS braces are not Twig).
	var cssEl = $frm.find('textarea[name=user_stylesheet]')[0];
	if(cssEl && window.CerbUI && CerbUI.ScriptingEditor)
		new CerbUI.ScriptingEditor(cssEl);
});
</script>
