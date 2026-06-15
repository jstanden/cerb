<div class="cerb-ui-header">
	<div>
		<div class="cerb-ui-header--title">Avatars</div>
		<div class="cerb-ui-header--subtitle"></div>
	</div>
</div>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupAvatars" class="cerb-ui-form">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="avatars">
<input type="hidden" name="action" value="saveJson">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.settings'|devblocks_translate|capitalize}</div>
	</div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Default picture on gendered contact records</label>
			<input type="hidden" name="avatar_default_style_contact" id="avatarStyleContact" value="{$avatar_default_style_contact}">
			<div>
				<div class="cerb-ui-switcher" data-cerb-input="avatarStyleContact">
					<button type="button" data-value="monograms" {if $avatar_default_style_contact=='monograms'}class="cerb-ui-switcher--active"{/if}>Monograms</button>
					<button type="button" data-value="silhouettes" {if $avatar_default_style_contact=='silhouettes'}class="cerb-ui-switcher--active"{/if}>Silhouettes</button>
				</div>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Default picture on gendered worker records</label>
			<input type="hidden" name="avatar_default_style_worker" id="avatarStyleWorker" value="{$avatar_default_style_worker}">
			<div>
				<div class="cerb-ui-switcher" data-cerb-input="avatarStyleWorker">
					<button type="button" data-value="monograms" {if $avatar_default_style_worker=='monograms'}class="cerb-ui-switcher--active"{/if}>Monograms</button>
					<button type="button" data-value="silhouettes" {if $avatar_default_style_worker=='silhouettes'}class="cerb-ui-switcher--active"{/if}>Silhouettes</button>
				</div>
			</div>
		</div>
	</div>
</div>

<div>
	<button type="button" id="btnSaveAvatars" class="cerb-ui-button cerb-u-anim-group"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $frm = $('#frmSetupAvatars');

	Devblocks.formDisableSubmit($frm);

	// Each segmented switcher mirrors its choice into the hidden input that carries the POST value
	if(window.CerbUI && CerbUI.Switcher) {
		$frm.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
			const input = document.getElementById(this.getAttribute('data-cerb-input'));
			new CerbUI.Switcher(this, {
				value: input ? input.value : null,
				onSelect: function(value) { if(input) input.value = value; }
			});
		});
	}

	$frm.find('#btnSaveAvatars')
		.click(function(e) {
			e.stopPropagation();
			Devblocks.saveAjaxForm($frm);
		})
	;
});
</script>
