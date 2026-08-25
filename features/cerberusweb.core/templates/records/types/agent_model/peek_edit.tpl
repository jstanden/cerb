{$peek_context = 'cerb.contexts.agent.model'}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" data-cerb-dialog-title="Agent Model">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="agent_model">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		{* The create/test flow reads top-to-bottom, left-to-right: Provider -> Endpoint URL -> Authentication
		   -> Model. Start with Provider (not Name): it's the choice everything downstream keys off, and the
		   Name is defaulted from the model you pick. *}
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'dao.agent_model.provider'|devblocks_translate|capitalize} <span class="cerb-ui-form--required">*</span></label>
				<select name="provider" id="provider_{$form_id}" autofocus="autofocus">
					<option value="">({'common.choose'|devblocks_translate|lower}…)</option>
					{foreach from=$providers item=provider}
						<option value="{$provider.id}" data-cerb-ui-icon="{$provider.icon}"{if $model->provider == $provider.id} selected="selected"{/if}>{$provider.label}</option>
					{/foreach}
				</select>
			</div>

			{* Endpoint URL, lifted ahead of Model so it feeds both the model-list Refresh and the Test call.
			   Blank means "(auto)" -- the provider's own default endpoint (the placeholder tracks the picked
			   provider). It's a first-class column that wins over any `api_endpoint_url:` in the params below. *}
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'dao.agent_model.api_endpoint_url'|devblocks_translate|capitalize}</label>
				<input type="text" name="api_endpoint_url" value="{$model->api_endpoint_url}" id="endpointInput_{$form_id}" placeholder="(auto)" autocomplete="off" spellcheck="false">
				<div class="cerb-ui-form--hint">Blank uses the provider's default endpoint. Suggests the provider's own endpoints as you type; free text for a self-hosted or proxied one.</div>
			</div>
		</div>

		{* Authentication + Model. With provider, endpoint, and key in place, Refresh pulls the live model list
		   from the provider itself -- the only way to know what a self-hosted or local endpoint has loaded. *}
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'dao.agent_model.connected_account_id'|devblocks_translate|capitalize}</label>
				<div class="cerb-ui-record-chooser" id="authChooser_{$form_id}">
					{if $connected_account}
						<li data-context-id="{$connected_account->id}" data-label="{$connected_account->name}"></li>
					{/if}
				</div>
				<div class="cerb-ui-form--hint">The connected account holding this provider's API key. A local provider needs none.</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'dao.agent_model.model'|devblocks_translate|capitalize}</label>
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
					<input type="text" name="model" value="{$model->model}" id="modelInput_{$form_id}" placeholder="claude-sonnet-5" autocomplete="off" spellcheck="false" style="flex:1 1 auto;">
					<button type="button" class="cerb-ui-button cerb-ui-button--subtle" id="modelRefresh_{$form_id}" title="Load models from the provider"><span class="cerb-icons cerb-icon-refresh" id="modelRefreshIcon_{$form_id}"></span></button>
				</div>
				<div id="modelResult_{$form_id}"></div>
				<div class="cerb-ui-form--hint" id="modelHint_{$form_id}"></div>
				<div class="cerb-ui-form--hint">Refresh loads the provider's live model list; picking one fills the Name and capabilities below. Free text, so a new id works the day it ships.</div>
			</div>
		</div>

		{* Verify the chain above (provider + endpoint + key + model) against the provider itself. Tests what's
		   ON SCREEN, not what's stored -- the point is catching a bad key here instead of mid-automation hours
		   later. Kept on its own line at the end of the chain. *}
		<div class="cerb-ui-form--field">
			<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
				<button type="button" class="cerb-ui-button cerb-ui-button--subtle" id="testButton_{$form_id}"><span class="cerb-icons cerb-icon-plug"></span> {'common.test'|devblocks_translate|capitalize}</button>
				<span id="testResult_{$form_id}"></span>
			</div>
			<div class="cerb-ui-form--hint">Sends one short message to the provider to verify the key and model string. This is a real (billed) request.</div>
		</div>

		{* Name + Status. The name IS the uri an automation references (`cerb:agent_model:<name>`), so it's
		   identifier-shaped and unique -- a handle, not a display label. It's copied from the model you pick. *}
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize} <span class="cerb-ui-form--required">*</span></label>
				<label class="cerb-ui-form--control">
					<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-bot-message"></span>
					<input type="text" name="name" value="{$model->name}" id="nameInput_{$form_id}" placeholder="sonnet-high" autocomplete="off" spellcheck="false">
				</label>
				<div class="cerb-ui-form--hint">Referenced as <code>cerb:agent_model:&lt;name&gt;</code>. Copied from the model you pick &mdash; edit freely.</div>
			</div>

			{$status = $model->status|intval}
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.status'|devblocks_translate|capitalize}</label>
				<div>
					<input type="hidden" name="status" id="status_{$form_id}" value="{$status}">
					<div class="cerb-ui-switcher" data-cerb-input="status_{$form_id}">
						<button type="button" data-value="0"{if $status == 0} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-circle-ok"></span> Available</button>
						<button type="button" data-value="1"{if $status == 1} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-lock"></span> Unlisted</button>
						<button type="button" data-value="2"{if $status == 2} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-ban"></span> {'common.disabled'|devblocks_translate|capitalize}</button>
					</div>
				</div>
				<div class="cerb-ui-form--hint"><b>Available</b> is offered by routers. <b>Unlisted</b> is skipped by routers but still runs when an automation names it. <b>Disabled</b> refuses every request.</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.priority'|devblocks_translate|capitalize} <span class="cerb-icons cerb-icon-sort-asc" title="0=first, 255=last; 50=default"></span></label>
				<div><input type="number" name="priority" min="0" max="255" value="{$model->priority|default:50}" style="width:5em;"></div>
				<div class="cerb-ui-form--hint">The order routed pools prefer this model in, when nothing asks for a different one. Leave it at <b>50</b> unless you want this model ahead of (or behind) the pack &mdash; equal priority falls back to the ratings, then name.</div>
			</div>
		</div>

		{* Display. `provider` can't name the VENDOR: most models arrive over the OpenAI-compatible API
		   (llama.cpp, LM Studio, vLLM, z.ai, Qwen), so a GLM model on `provider: openai` would otherwise paint
		   the OpenAI logo everywhere. Both blanks fall back to the provider's own name/mark. These are stamped
		   onto every session this model primes, so a transcript keeps the vendor it actually ran as. *}
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'dao.agent_model.label'|devblocks_translate|capitalize}</label>
				<input type="text" name="label" value="{$model->label}" id="labelInput_{$form_id}" placeholder="{if $model->name}{$model->name}{else}GLM 4.6{/if}">
				<div class="cerb-ui-form--hint">A friendly name for pickers. Blank uses the Name above.</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'dao.agent_model.icon'|devblocks_translate|capitalize}</label>
				{* Both pickers WRAP their input in a well that takes its place in the flow, so sizing hints belong
				   on the wrapper, not the inputs — leave them unstyled and let the wells sit side by side. *}
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
					<input type="text" name="icon" value="{$model->icon}" id="iconInput_{$form_id}" autocomplete="off" spellcheck="false">
					<input type="text" name="icon_color" value="{$model->icon_color}" id="iconColorInput_{$form_id}" autocomplete="off" spellcheck="false">
				</div>
				<div class="cerb-ui-form--hint">Overrides the provider's brand mark and color. Blank uses the provider's own.</div>
			</div>
		</div>


		<div class="cerb-ui-form--section">
			<div class="cerb-ui-form--section-head">Capabilities</div>
			<div class="cerb-ui-form--section-body">
				<div class="cerb-ui-form--row">
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'dao.agent_model.context_window'|devblocks_translate|capitalize}</label>
						<input type="number" name="context_window" value="{if $model->context_window}{$model->context_window}{/if}" id="contextWindowInput_{$form_id}" placeholder="200000" min="0" step="1000">
						<div class="cerb-ui-form--hint">Tokens. Compaction ratios are fractions of this.</div>
					</div>

					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'dao.agent_model.has_vision'|devblocks_translate|capitalize}</label>
						<div>
							<input type="hidden" name="has_vision" id="hasVision_{$form_id}" value="{$model->has_vision}">
							<div class="cerb-ui-switcher" data-cerb-input="hasVision_{$form_id}">
								<button type="button" data-value="1"{if $model->has_vision} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-eye-open"></span> {'common.yes'|devblocks_translate|capitalize}</button>
								<button type="button" data-value="0"{if !$model->has_vision} class="cerb-ui-switcher--active"{/if}>{'common.no'|devblocks_translate|capitalize}</button>
							</div>
						</div>
						<div class="cerb-ui-form--hint">Can accept images.</div>
					</div>

					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">Thinking</label>
						<div>
							<input type="hidden" name="has_thinking" id="hasThinking_{$form_id}" value="{$model->has_thinking}">
							<div class="cerb-ui-switcher" data-cerb-input="hasThinking_{$form_id}">
								<button type="button" data-value="1"{if $model->has_thinking} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-brain"></span> {'common.yes'|devblocks_translate|capitalize}</button>
								<button type="button" data-value="0"{if !$model->has_thinking} class="cerb-ui-switcher--active"{/if}>{'common.no'|devblocks_translate|capitalize}</button>
							</div>
						</div>
						<div class="cerb-ui-form--hint">Supports extended reasoning.</div>
					</div>
				</div>
			</div>
		</div>

		<div class="cerb-ui-form--section">
			<div class="cerb-ui-form--section-head">Ratings</div>
			<div class="cerb-ui-form--section-body">
				<div class="cerb-ui-form--row">
					{include file="devblocks:cerberusweb.core::records/types/agent_model/rating.tpl"
						form_id=$form_id key='intelligence' name='rating_intelligence' label='Intelligence'
						icon=$rating_glyphs.intelligence color=$rating_colors.intelligence|default:''
						value=$model->rating_intelligence scale=$rating_scales.intelligence
						hint='Higher is better. How capable, relative to the field today.'}

					{include file="devblocks:cerberusweb.core::records/types/agent_model/rating.tpl"
						form_id=$form_id key='speed' name='rating_speed' label='Speed'
						icon=$rating_glyphs.speed color=$rating_colors.speed|default:''
						value=$model->rating_speed scale=$rating_scales.speed
						hint='Higher is better. More tokens per second.'}

					{include file="devblocks:cerberusweb.core::records/types/agent_model/rating.tpl"
						form_id=$form_id key='privacy' name='rating_privacy' label='Privacy'
						icon=$rating_glyphs.privacy color=$rating_colors.privacy|default:''
						value=$model->rating_privacy scale=$rating_scales.privacy
						hint='Higher is better. Less retention and disclosure.'}

					{include file="devblocks:cerberusweb.core::records/types/agent_model/rating.tpl"
						form_id=$form_id key='cost' name='rating_cost' label='Cost'
						icon=$rating_glyphs.cost color=$rating_colors.cost|default:''
						value=$model->rating_cost scale=$rating_scales.cost
						hint='Lower is better. Price per token and caching.'}
				</div>
			</div>
		</div>

		{* The open tail of the provider's `llm:` block. The PROVIDER EXTENSION supplies these keys and their
		   value lists (getChatKataAutocomplete), so each provider contributes its own config surface here —
		   cache/effort/thinking/compaction/api_endpoint_url, and anything a provider adds later, with no
		   schema change. Provider-specific knobs live HERE rather than as columns: only some providers accept
		   an endpoint override, so a fixed field would lie about the ones that don't. *}
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'dao.agent_model.params_kata'|devblocks_translate|capitalize}</label>
			<textarea name="params_kata" id="paramsKata_{$form_id}" rows="8" spellcheck="false">{$model->params_kata}</textarea>
			<div class="cerb-ui-form--hint">Provider knobs in <code>llm:</code> grammar. The fields above win where they overlap.</div>
		</div>

		{if !empty($custom_fields)}
			{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		{/if}
	</div>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="agent model" detail="Automations that reference it will fail."}
{/if}

<div class="buttons" style="margin-top:10px;">
	{if $model->id}
		<button type="button" class="save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
		<button type="button" class="save-continue"><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
		{if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="delete-prompt"><span class="cerb-icons cerb-icon-trash cerb-u-anim-shake-hover"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
	{else}
		<button type="button" class="save"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
	{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	// Per-provider catalog from the provider EXTENSIONS: model suggestions, the default endpoint, and that
	// provider's own `llm:` autocomplete re-keyed to this editor's root.
	const providers = {$providers_json nofilter};

	$popup.one('popup_open', function(event,ui) {
		$popup.find('[autofocus]:first').focus();
		$popup.css('overflow', 'inherit');

		const providerEl = document.getElementById('provider_{$form_id}');
		const endpointEl = document.getElementById('endpointInput_{$form_id}');

		function currentProvider() {
			return (providerEl && providers[providerEl.value]) ? providers[providerEl.value] : null;
		}

		// The endpoint field is an OVERRIDE: its placeholder shows the picked provider's own default so a blank
		// field reads honestly as "(auto)" -> that default. Only the placeholder tracks the provider; a value
		// the operator typed is theirs to keep.
		function syncEndpointPlaceholder() {
			if(!endpointEl) return;
			const provider = currentProvider();
			endpointEl.placeholder = (provider && provider.endpoint_default) ? provider.endpoint_default : '(auto)';
		}

		// The placeholder above only helps until you type. Every provider already ships the endpoints it
		// actually serves -- its `api_endpoint_url:` KATA values, re-keyed into the catalog -- so suggest
		// them. It earns its keep on AWS Bedrock, where the URL is a per-region host you'd otherwise have to
		// remember. Still free text: a self-hosted or proxied endpoint just isn't in the list.
		if(endpointEl && window.CerbUI && CerbUI.TextChooser) {
			new CerbUI.TextChooser(endpointEl, {
				icon: 'globe',
				source: function(term) {
					const provider = currentProvider();
					const pool = (provider && provider.params) ? (provider.params['api_endpoint_url:'] || []) : [];
					const needle = String(term || '').toLowerCase();

					// A value list may hold a typed descriptor object (a `type` key, as `authentication:` does);
					// only the plain strings are urls.
					return pool.filter(url => typeof url === 'string' && url.toLowerCase().indexOf(needle) >= 0);
				}
			});
		}

		// Each option carries its provider's brand icon (data-cerb-ui-icon), which SelectMenu renders on both
		// the dropdown row and the trigger. The native <select> stays in the DOM (hidden) and still posts.
		if(providerEl && window.CerbUI && CerbUI.SelectMenu) {
			new CerbUI.SelectMenu(providerEl, {
				placeholder: "{'common.choose'|devblocks_translate|lower|escape:'javascript' nofilter}…"
			});
		}

		// The icon/color overrides. Both are plain inputs that post normally; the pickers only enhance them.
		// The icon well's EMPTY glyph tracks the picked provider, so a blank field previews exactly what it
		// falls back to rather than a generic placeholder.
		const iconEl = document.getElementById('iconInput_{$form_id}');
		const iconColorEl = document.getElementById('iconColorInput_{$form_id}');
		let iconPicker = null;

		if(iconEl && window.CerbUI && CerbUI.IconPicker) {
			iconPicker = new CerbUI.IconPicker(iconEl, {
				emptyIcon: 'bot',
				allowClear: true
			});
		}

		// Swatch-only, like every other ColorPicker in the app: the hex belongs in the popup (which grows its
		// own field in this mode), not sitting in the form next to the icon well. The <input> stays in the DOM
		// and still posts `icon_color`.
		if(iconColorEl && window.CerbUI && CerbUI.ColorPicker) {
			new CerbUI.ColorPicker(iconColorEl, {
				showInput: false
			});
		}

		// Repaint the well, NOT setValue() -- that fires input/change unconditionally, which would dirty the
		// form just because someone switched providers.
		function syncIconFallback() {
			if(!iconPicker) return;
			const provider = currentProvider();
			iconPicker.opts.emptyIcon = (provider && provider.icon) ? provider.icon : 'bot';
			if(typeof iconPicker._render === 'function') iconPicker._render();
		}

		// SelectMenu writes back to the native <select> and fires `change` on it, so the endpoint placeholder
		// and the icon fallback follow the picked provider.
		if(providerEl) {
			providerEl.addEventListener('change', syncEndpointPlaceholder);
			providerEl.addEventListener('change', syncIconFallback);
			syncEndpointPlaceholder();
			syncIconFallback();
		}

		if(window.CerbUI && CerbUI.Switcher) {
			$popup.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
				const input = document.getElementById(this.getAttribute('data-cerb-input'));
				new CerbUI.Switcher(this, {
					value: input ? input.value : null,
					onSelect: function(value) { if(input) input.value = value; }
				});
			});
		}

		// Shared chip builder (Connected/Failed for Test; the model Refresh error). The provider's own error
		// text is the useful part (invalid_api_key, model_not_found, a 404 on a bad endpoint), so it's always
		// rendered as TEXT -- never interpolated as markup.
		function chipCell(label, value) {
			const cell = document.createElement('div');
			const labelEl = document.createElement('div');
			labelEl.className = 'cerb-ui-chip--label';
			labelEl.textContent = label;
			const valueEl = document.createElement('div');
			valueEl.className = 'cerb-ui-chip--value';
			valueEl.textContent = value;
			cell.appendChild(labelEl);
			cell.appendChild(valueEl);
			return cell;
		}

		function buildChip(icon, head, cells) {
			const chip = document.createElement('div');
			chip.className = 'cerb-ui-chip';

			const headEl = document.createElement('div');
			headEl.className = 'cerb-ui-chip--head';
			headEl.innerHTML = '<span class="cerb-icons cerb-icon-' + icon + ' cerb-u-mr-1"></span>';
			headEl.appendChild(document.createTextNode(head));
			chip.appendChild(headEl);

			cells.forEach(cell => chip.appendChild(cell));
			return chip;
		}

		// The live model list, loaded ON DEMAND by the Refresh button (not lazily on focus -- a silent fallback
		// to the shipped list made a failed fetch look like "nothing happened"). Until a refresh succeeds the
		// Model field is free text with the shipped ids as plain hints. `modelMeta` carries each id's
		// provider-known defaults (vision, context window) for the fill-on-select below.
		const modelInputEl = document.getElementById('modelInput_{$form_id}');
		const modelRefresh = document.getElementById('modelRefresh_{$form_id}');
		const modelRefreshIcon = document.getElementById('modelRefreshIcon_{$form_id}');
		const modelResult = document.getElementById('modelResult_{$form_id}');
		let liveModels = null;
		let modelMeta = { };

		function refreshModels() {
			const provider = currentProvider();

			if(!provider) {
				modelResult.replaceChildren(buildChip('circle-remove', 'Failed', [chipCell('Error', 'Choose a provider first.')]));
				return;
			}

			const paramsEl = document.getElementById('paramsKata_{$form_id}');
			const authEl = $frm.find('[name=connected_account_id]');

			const args = {
				c: 'profiles',
				a: 'invoke',
				module: 'agent_model',
				action: 'modelsJson',
				provider: providerEl ? providerEl.value : '',
				api_endpoint_url: endpointEl ? endpointEl.value : '',
				connected_account_id: authEl.length ? (authEl.val() || 0) : 0,
				params_kata: paramsEl ? paramsEl.value : ''
			};

			if(modelRefreshIcon) modelRefreshIcon.classList.add('cerb-u-anim-spin');
			if(modelRefresh) modelRefresh.disabled = true;
			modelResult.replaceChildren();

			genericAjaxPost(args, null, '', function(json) {
				if(modelRefreshIcon) modelRefreshIcon.classList.remove('cerb-u-anim-spin');
				if(modelRefresh) modelRefresh.disabled = false;

				if(!json || !json.status || !Array.isArray(json.models)) {
					modelResult.replaceChildren(buildChip('circle-remove', 'Failed', [chipCell('Error', (json && json.error) ? json.error : 'The request failed.')]));
					return;
				}

				// The endpoint returns one object per model (id + optional has_vision/context_window/description);
				// keep the ids for suggestions and the per-id metadata for fill-on-select. A live list REPLACES
				// the shipped one -- for a local endpoint, ours would be noise.
				modelMeta = {};
				liveModels = json.models.map(function(row) {
					const id = (row && typeof row === 'object') ? String(row.id) : String(row);
					if(row && typeof row === 'object') modelMeta[id] = row;
					return id;
				});

				const count = document.createElement('span');
				count.className = 'cerb-u-fgg-4 cerb-u-fs-2';
				count.textContent = liveModels.length + (liveModels.length === 1 ? ' model' : ' models');
				modelResult.replaceChildren(count);

				const tc = CerbUI.TextChooser.from(modelInputEl);
				if(tc) tc.open();

			}, { error: function() {
				if(modelRefreshIcon) modelRefreshIcon.classList.remove('cerb-u-anim-spin');
				if(modelRefresh) modelRefresh.disabled = false;
				modelResult.replaceChildren(buildChip('circle-remove', 'Failed', [chipCell('Error', 'The request failed.')]));
			}});
		}

		if(modelRefresh)
			modelRefresh.addEventListener('click', refreshModels);

		// Picking a model is the pivot of the flow: it fills the Name (a sanitized handle) and, from the
		// provider's metadata, the capability fields -- so the common case is pick-and-save.
		if(window.CerbUI && CerbUI.TextChooser && modelInputEl) {
			new CerbUI.TextChooser(modelInputEl, {
				icon: 'bot-message',
				source: function(term) {
					const provider = currentProvider();
					if(!provider) return [];

					// A refreshed live list wins; otherwise the shipped ids are plain free-text hints.
					const pool = (liveModels && liveModels.length) ? liveModels : (provider.models || []);
					const needle = String(term || '').toLowerCase();
					return pool.filter(model => model.toLowerCase().indexOf(needle) >= 0);
				},
				onSelect: function(item, input) {
					const value = (item.value != null) ? String(item.value) : String(item.label);
					input.value = value;

					// Copy a sanitized handle into Name (the uri is alnum/dot/dash/underscore). Overwrites --
					// it's a default the operator can fix on save.
					const nameEl = document.getElementById('nameInput_{$form_id}');
					if(nameEl) {
						nameEl.value = value.replace(/[^A-Za-z0-9_.-]/g, '');
						nameEl.dispatchEvent(new Event('input', { bubbles: true }));
					}

					// Default capabilities from the model's provider metadata -- only the keys it actually
					// carries, so a metadata-less model never clears what's already typed.
					const meta = modelMeta[value];
					if(meta) {
						const hintEl = document.getElementById('modelHint_{$form_id}');
						if(hintEl) hintEl.textContent = ('description' in meta) ? String(meta.description || '') : '';

						if('context_window' in meta) {
							const cwEl = document.getElementById('contextWindowInput_{$form_id}');
							if(cwEl) cwEl.value = meta.context_window || '';
						}
						if('has_vision' in meta) {
							const visionEl = document.getElementById('hasVision_{$form_id}');
							const visionSwitcher = visionEl ? CerbUI.Switcher.from(visionEl.parentNode.querySelector('.cerb-ui-switcher')) : null;
							const visionValue = meta.has_vision ? '1' : '0';
							if(visionEl) visionEl.value = visionValue;
							if(visionSwitcher) visionSwitcher.setValue(visionValue);
						}
					}
				}
			});
		}

		if(window.CerbUI && CerbUI.Rating) {
			$frm.find('.cerb-ui-rating').each(function() {
				new CerbUI.Rating(this);
			});
		}

		if(window.CerbUI && CerbUI.RecordChooser) {
			new CerbUI.RecordChooser(document.getElementById('authChooser_{$form_id}'), {
				context: 'cerberusweb.contexts.connected_account',
				name: 'connected_account_id',
				emptyIcon: 'key',
				searchPlaceholder: 'Connected account…'
			});
		}

		// The params editor autocompletes the SELECTED provider's keys. Wrapped rather than bound to a static
		//  map, so switching providers switch the suggestions without rebuilding the editor (which would
		// throw away what's already typed).
		if(window.CerbUI && CerbUI.KataEditor) {
			const paramsEl = document.getElementById('paramsKata_{$form_id}');

			if(paramsEl) {
				new CerbUI.KataEditor(paramsEl, {
					minLines: 6,
					maxLines: 20,
					onAutocomplete: function() {
						const provider = currentProvider();
						const source = CerbUI.KataEditor.kataFieldSource((provider && provider.params) ? provider.params : {});
						return source.apply(this, arguments);
					}
				});
			}
		}

		// One real chat turn against the CURRENT form values (never the stored record, never a save). The
		// params are assembled explicitly rather than serializing $frm, which also carries `action=savePeekJson`,
		// `do_delete`, and the custom fields -- none of which belong in this request.
		const testButton = document.getElementById('testButton_{$form_id}');
		const testResult = document.getElementById('testResult_{$form_id}');

		if(testButton && testResult) {
			testButton.addEventListener('click', function() {
				const paramsEl = document.getElementById('paramsKata_{$form_id}');
				const authEl = $frm.find('[name=connected_account_id]');

				const params = {
					c: 'profiles',
					a: 'invoke',
					module: 'agent_model',
					action: 'testJson',
					provider: providerEl ? providerEl.value : '',
					model: $frm.find('[name=model]').val() || '',
					api_endpoint_url: endpointEl ? endpointEl.value : '',
					connected_account_id: authEl.length ? (authEl.val() || 0) : 0,
					has_vision: $frm.find('[name=has_vision]').val() || 0,
					context_window: $frm.find('[name=context_window]').val() || 0,
					params_kata: paramsEl ? paramsEl.value : ''
				};

				const spinner = Devblocks.getSpinner().css('max-width', '16px');
				testResult.replaceChildren(spinner[0]);
				testButton.disabled = true;

				// An OBJECT formRef becomes FormData (a string would be read as an element id); CSRF rides the
				// X-CSRF-Token header that genericAjaxPost always sets.
				genericAjaxPost(params, null, '', function(json) {
					testButton.disabled = false;

					if(!json || !json.status) {
						testResult.replaceChildren(buildChip('circle-remove', 'Failed', [
							chipCell('Error', (json && json.error) ? json.error : 'The request failed.')
						]));
						return;
					}

					const usage = json.usage || {};
					const cells = [chipCell('Model', json.model || '(default)')];

					// Only providers that front more than one wire format report a surface; the rest send ''.
					if(json.api_surface)
						cells.push(chipCell('API', json.api_surface));

					if(json.reply)
						cells.push(chipCell('Reply', json.reply));

					cells.push(chipCell('Tokens', (usage.input || 0) + ' in / ' + (usage.output || 0) + ' out'));
					cells.push(chipCell('Time', json.elapsed_ms + ' ms'));

					testResult.replaceChildren(buildChip('circle-ok', 'Connected', cells));

				}, { error: function() {
					testButton.disabled = false;
					testResult.replaceChildren(buildChip('circle-remove', 'Failed', [chipCell('Error', 'The request failed.')]));
				}});
			});
		}

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

		// Inline delete confirm (reveals the cerb-ui-panel--alert, hides the button row); the actual
		// delete stays on button.delete above.
		if(window.CerbUI && CerbUI.Form)
			CerbUI.Form.ConfirmDelete($popup[0]);
	});
});
</script>
