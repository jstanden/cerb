{$wizard_uid = uniqid('abwiz')}
<style>{literal}
/* Wizard layout only — the model/tool card styles live in cerb-ui/_agentprompt.scss (shared with Form Builder). */
.cerb-ab-wizard--title { display:flex; align-items:center; gap:0.4em; margin-bottom:0.75em; font-weight:bold; font-size: 1.5em; }
.cerb-ab-wizard--split { min-height:340px; }
.cerb-ab-wizard--left, .cerb-ab-wizard--right { min-width:0; padding:0 0.6em; box-sizing:border-box; overflow:auto; }
.cerb-ab-wizard--section { margin-bottom:1em; }
{/literal}</style>

<div id="{$wizard_uid}" data-cerb-agent-chat-wizard>
	<script type="application/json" data-cerb-wizard-models>{$agent_models_json nofilter}</script>

	<div class="cerb-ab-wizard--title"><span class="cerb-icons cerb-icon-bot-message"></span> Agent Chat</div>

	<div class="cerb-ab-wizard--errors cerb-ui-panel--alert" data-cerb-wizard-errors hidden style="margin-bottom:0.75em;"></div>

	<div class="cerb-ab-wizard--split">
		<div class="cerb-ab-wizard--left">
			<div class="cerb-ab-wizard--section cerb-ui-form">
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">Chat title</label>
					<input type="text" data-cerb-wizard-title value="Agent Chat" spellcheck="false" style="width:100%; box-sizing:border-box;">
				</div>
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">System prompt</label>
					<textarea data-cerb-wizard-system rows="5" spellcheck="false" style="width:100%; box-sizing:border-box;">You are a helpful AI agent.</textarea>
				</div>
			</div>

			<div class="cerb-ab-wizard--section" data-cerb-agent-tool-picker></div>
		</div>

		<div class="cerb-ab-wizard--right">
			<div class="cerb-ab-wizard--section" data-cerb-agent-model-picker></div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
{literal}
(function() {
	var root = document.getElementById('{/literal}{$wizard_uid}{literal}');
	if(!root) return;

	var parse = function(sel) { try { return JSON.parse(root.querySelector(sel).textContent); } catch(e) { return null; } };
	var AM = parse('[data-cerb-wizard-models]') || [];

	var hasAP = window.CerbUI && CerbUI.AgentPrompt;

	var picker = (hasAP && CerbUI.AgentPrompt.ModelPicker)
		? new CerbUI.AgentPrompt.ModelPicker(root.querySelector('[data-cerb-agent-model-picker]'), { agentModels: AM })
		: null;

	var tools = (hasAP && CerbUI.AgentPrompt.ToolPicker)
		? new CerbUI.AgentPrompt.ToolPicker(root.querySelector('[data-cerb-agent-tool-picker]'), { context: 'cerb.contexts.automation', query: 'trigger:cerb.trigger.llm.tool' })
		: null;

	// 2:1 split — system prompt + tools on the left, the (narrower) model cards on the right.
	if(window.CerbUI && CerbUI.SplitPane) {
		try { new CerbUI.SplitPane(root.querySelector('.cerb-ab-wizard--split'), { orientation: 'horizontal', ratio: 0.667, min: 240 }); } catch(e) {}
	}

	var errorsEl = root.querySelector('[data-cerb-wizard-errors]');
	var showErrors = function(list) {
		if(!errorsEl) return;
		errorsEl.textContent = (list && list.length) ? list.join(' ') : '';
		errorsEl.hidden = !(list && list.length);
	};

	// The Automation Builder reads answers off the wizard-body element on Apply.
	var body = root.closest('[data-cerb-template-wizard-body]') || root.parentElement;
	body._cerbGetAnswers = function() {
		var sys = root.querySelector('[data-cerb-wizard-system]');
		var titleEl = root.querySelector('[data-cerb-wizard-title]');
		return {
			title: (titleEl && titleEl.value) || '',
			system_prompt: (sys && sys.value) || '',
			tools: tools ? tools.getTools() : [],
			models: picker ? picker.getModels() : []
		};
	};

	// Block "Use this template" until the config is valid (returns true when OK).
	body._cerbValidate = function() {
		var errs = [];
		var models = picker ? picker.getModels() : [];
		if(!models.length)
			errs.push('Add at least one model.');
		showErrors(errs);
		return errs.length === 0;
	};
})();
{/literal}
</script>
