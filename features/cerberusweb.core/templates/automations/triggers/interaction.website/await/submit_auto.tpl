{$element_uid = uniqid('el_')}
{if $is_automation_simulated|default:false}
	{* Design-time preview / simulator form-fill: don't auto-submit — show an inert marker so the builder
	   reflects the element. *}
	<div id="{$element_uid}" class="cerb-u-text-muted" style="display:flex;align-items:center;gap:0.4em;">
		<span class="cerb-icons cerb-icon-send"></span> Automatic submit
	</div>
{else}
	<div id="{$element_uid}" class="cerb-interaction-popup--form-elements-submit-auto">
	</div>

	<script type="text/javascript" nonce="{$session->nonce}">
	{
		let $element = document.querySelector('#{$element_uid}');
		let $popup = $element.closest('.cerb-interaction-popup');

		$element.style.display = 'none';
		$popup.dispatchEvent($$.createEvent('cerb-interaction-event--submit'));
	}
	</script>
{/if}