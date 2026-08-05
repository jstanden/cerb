{$element_uid = uniqid('uicmd_')}
{if $is_automation_form_error|default:false}
	{* Error re-render: do NOT fire the round-trip (it would re-submit and re-trigger the same error forever).
	   Render nothing — the worker sees the validation error + a manual Continue instead. *}
{elseif $disabled|default:false}
	{* Inert: emit the (empty) return var so the field still validates and its value resolves to '', but skip the
	   host round-trip. Lets one await:form carry several uiCommands and enable just the one matching the current
	   tool via a disabled@bool: expression on each. *}
	<input type="hidden" name="prompts[{$var}]" value="">
{elseif $is_automation_simulated|default:false}
	{* No host editor + cerbBotTrigger is attached here (the form-builder preview / the state simulator), so the
	   command can't round-trip. Offer an input to supply the value the command WOULD return — text or JSON — which
	   rides prompts[<var>] into state exactly like the live result. (The Input state editor can also prime it.) *}
	<div class="cerb-form-builder-prompt cerb-form-builder-prompt-uicommand" id="{$element_uid}">
		<h6><span class="cerb-icons cerb-icon-console"></span> UI command: {$command}</h6>

		<div style="margin-left:10px;">
			<div class="cerb-u-text-muted" style="margin-bottom:0.3em;">No UI editor attached &mdash; enter the value this command would return (text or JSON):</div>
			<textarea name="prompts[{$var}]" rows="3" spellcheck="false" style="width:100%;box-sizing:border-box;"></textarea>
		</div>
	</div>
{else}
	<span id="{$element_uid}" style="display:none;"></span>
	<input type="hidden" name="prompts[{$var}]" value="">

	<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
	$(function() {
		var el = document.getElementById('{$element_uid}');
		if(!el) return;

		// uiCommand does NOT submit — it only FILLS its hidden field; the form's `submit:` posts it. cerbBotTrigger
		// stored the host's command callback on the form, so invoke it SYNCHRONOUSLY here (this element renders
		// before the submit, so a sync result is in place before submit_auto fires). No host command (e.g. run
		// outside its editor) → the field stays empty and the submit posts it as such; the interaction never hangs.
		var form = el.closest('form.cerb-form-builder');
		var input = form ? form.querySelector('input[name="prompts[{$var}]"]') : null;
		var cmd = form ? form._cerbInteractionCommand : null;
		if(!input || typeof cmd !== 'function') return;

		try {
			var result = cmd({$command_json nofilter}, {$params_json nofilter});
			if(result && typeof result.then === 'function') {
				result.then(function(v) { input.value = (v == null) ? '' : String(v); }, function() { input.value = ''; });
			} else {
				input.value = (result == null) ? '' : String(result);
			}
		} catch(e) {
			input.value = '';
		}
	});
	</script>
{/if}
