{$peek_context = 'cerb.contexts.agent.model.router'}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" class="cerb-ui-form">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="agent_model_router">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
	<input type="text" name="name" value="{$model->name}" placeholder="default" autocomplete="off" spellcheck="false" autofocus="autofocus">
	<div class="cerb-ui-form--hint">The router's URI handle &mdash; <code>cerb:agent_model_router:&lt;name&gt;</code>. Letters, numbers, dots, dashes, underscores.</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.label'|devblocks_translate|capitalize}</label>
	<input type="text" name="label" value="{$model->label}" placeholder="Default" autocomplete="off" spellcheck="false">
	<div class="cerb-ui-form--hint">The friendly name shown in pickers. Blank uses the name.</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.description'|devblocks_translate|capitalize}</label>
	<input type="text" name="description" value="{$model->description}" autocomplete="off">
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.models'|devblocks_translate|capitalize}</label>
	<div class="cerb-ui-searchquery" data-cerb-searchquery>
		<span class="cerb-ui-searchquery--icon cerb-icons cerb-icon-search"></span>
		<div class="cerb-ui-searchquery--field">
			<div class="cerb-ui-searchquery--highlight" aria-hidden="true"></div>
			<textarea name="models_query" class="cerb-ui-searchquery--input" rows="1" placeholder="(all available models)">{$model->models_query}</textarea>
			<span class="cerb-ui-searchquery--caret-anchor"></span>
		</div>
		<div class="cerb-ui-searchquery--right">
			<a data-action="autocomplete" class="cerb-u-cursor-pointer cerb-u-text-muted" title="Suggestions (Ctrl/&#8984;+Space)"><span class="cerb-icons cerb-icon-autocomplete"></span></a>
		</div>
	</div>
	<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2" style="margin-top:0.4em;">
		<button type="button" class="cerb-ui-button cerb-ui-button--subtle" id="previewButton_{$form_id}"><span class="cerb-icons cerb-icon-play" id="previewIcon_{$form_id}"></span> {'common.preview'|devblocks_translate|capitalize}</button>
		<span id="previewCount_{$form_id}"></span>
	</div>
	<div id="previewResult_{$form_id}" style="display:none;margin-top:0.5em;"></div>
	<div class="cerb-ui-form--hint">
		Optional. Blank offers every available model &mdash; narrow with e.g.
		<code>hasVision:y sort:-intelligence</code>. Unlisted and disabled models are always excluded,
		and a model that starts matching appears here with no edit.
	</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Overrides</label>
	<textarea name="models_kata" rows="6" spellcheck="false" style="width:100%;">{$model->models_kata}</textarea>
	<div class="cerb-ui-form--hint">
		Optional per-model settings, keyed by model name. Entries the search above didn't match are ignored.
		Use <code>&lt;name&gt;/&lt;alias&gt;:</code> to offer one model more than once with different settings.
	</div>
</div>


</div>



{if !empty($custom_fields)}
<table cellspacing="0" cellpadding="2" border="0" width="98%">
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
</table>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>

	<div>
		Are you sure you want to permanently delete this agent model router?
	</div>

	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" class="delete-cancel">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="buttons" style="margin-top:10px;">
	{if $model->id}
		<button type="button" class="save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
		<button type="button" class="save-continue"><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
		{if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="delete-prompt"><span class="cerb-icons cerb-icon-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
	{else}
		<button type="button" class="save"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
	{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);
	const modelsAutocomplete = {$models_autocomplete_json nofilter};

	Devblocks.formDisableSubmit($frm);

	if(window.CerbUI && CerbUI.Toggle)
		$frm.find('.cerb-ui-toggle').each(function() { new CerbUI.Toggle(this); });

	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'Agent Model Router'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.find('[autofocus]:first').focus();
		$popup.css('overflow', 'inherit');

		// Runs the query ON SCREEN, so it answers "is this what I meant?" before saving.
		const previewButton = document.getElementById('previewButton_{$form_id}');
		const previewIcon = document.getElementById('previewIcon_{$form_id}');
		const previewCount = document.getElementById('previewCount_{$form_id}');
		const previewResult = document.getElementById('previewResult_{$form_id}');

		if(previewButton) {
			previewButton.addEventListener('click', function() {
				const queryEl = $frm.find('textarea[name=models_query]')[0];

				// SearchQuery edits don't fire `input`, so read the instance rather than the element.
				const sq = (window.CerbUI && CerbUI.SearchQuery) ? CerbUI.SearchQuery.from(queryEl) : null;
				const query = sq ? sq.getValue() : (queryEl ? queryEl.value : '');

				previewButton.disabled = true;
				previewIcon.classList.add('cerb-u-anim-spin');
				previewCount.textContent = '';

				const args = {
					c: 'profiles',
					a: 'invoke',
					module: 'agent_model_router',
					action: 'previewModelsJson',
					models_query: query
				};

				genericAjaxPost(args, null, '', function(json) {
					previewButton.disabled = false;
					previewIcon.classList.remove('cerb-u-anim-spin');

					if(!json || !json.status) {
						previewResult.className = 'cerb-ui-panel cerb-ui-panel--alert';
						previewResult.style.display = 'block';
						previewResult.textContent = (json && json.error) ? json.error : 'The request failed.';
						return;
					}

					previewCount.className = 'cerb-u-fgg-7';
					// "offered", not just a count: the router also excludes unlisted/disabled models, so this
					// legitimately differs from what the same query returns in Search.
					previewCount.textContent = json.count + (1 === json.count ? ' model offered' : ' models offered');

					previewResult.replaceChildren();
					previewResult.className = 'cerb-ui-panel';

					const clear = document.createElement('a');
					clear.className = 'cerb-u-cursor-pointer cerb-u-fgg-7';
					clear.style.float = 'right';
					clear.title = "{'common.clear'|devblocks_translate|capitalize|escape:'javascript' nofilter}";
					clear.innerHTML = '<span class="cerb-icons cerb-icon-erase"></span>';
					clear.addEventListener('click', function() {
						previewResult.replaceChildren();
						previewResult.style.display = 'none';
						previewCount.textContent = '';
					});
					previewResult.appendChild(clear);

					if(!json.count) {
						previewResult.style.display = 'block';
						const empty = document.createElement('div');
						empty.className = 'cerb-u-fgg-7';
						empty.textContent = 'No models match. This router would fail to resolve.';
						previewResult.appendChild(empty);
						return;
					}

					const table = document.createElement('table');
					table.style.width = '100%';
					table.style.borderCollapse = 'collapse';

					// The rating COLUMN names the axis, so a cell is just its tier -- no `privacy: local` labels.
					const columns = [
						{ key: 'name', label: 'Name' },
						{ key: 'model', label: 'Model' },
						{ key: 'provider', label: 'Provider' },
						{ key: 'vision', label: 'Vision', center: true },
						{ key: 'thinking', label: 'Thinking', center: true },
						{ key: 'intelligence', label: 'Intelligence' },
						{ key: 'speed', label: 'Speed' },
						{ key: 'privacy', label: 'Privacy' },
						{ key: 'cost', label: 'Cost' }
					];

					const thead = document.createElement('thead');
					const htr = document.createElement('tr');

					columns.forEach(function(col) {
						const th = document.createElement('th');
						th.className = 'cerb-u-py-1 cerb-u-pr-2 cerb-u-fw-600 cerb-u-fgg-7 cerb-u-text-uppercase cerb-u-border-b-1 cerb-u-bdg-4'
							+ (col.center ? ' cerb-u-text-center' : '');
						th.style.textAlign = col.center ? 'center' : 'left';
						th.textContent = col.label;
						htr.appendChild(th);
					});

					thead.appendChild(htr);
					table.appendChild(thead);

					const tbody = document.createElement('tbody');

					json.models.forEach(function(model) {
						const tr = document.createElement('tr');

						columns.forEach(function(col) {
							const td = document.createElement('td');
							td.className = 'cerb-u-py-1 cerb-u-pr-2';
							if(col.center) td.style.textAlign = 'center';

							if('name' === col.key) {
								// Opens the model's own peek, so a wrong rating is fixed where you noticed it.
								const link = document.createElement('a');
								link.className = 'cerb-peek-trigger cerb-u-cursor-pointer';
								link.setAttribute('data-context', 'cerb.contexts.agent.model');
								link.setAttribute('data-context-id', model.id);

								const icon = document.createElement('span');
								icon.className = 'cerb-icons cerb-icon-' + model.icon;
								icon.style.marginRight = '0.4em';
								icon.style.verticalAlign = 'middle';
								if(model.icon_color) icon.style.color = model.icon_color;
								link.appendChild(icon);
								link.appendChild(document.createTextNode(model.name));
								td.appendChild(link);

							} else if('model' === col.key) {
								td.className += ' cerb-u-fgg-7';
								td.textContent = model.model || '';

							} else if('provider' === col.key) {
								td.className += ' cerb-u-fgg-7';
								td.textContent = model.provider || '';

							} else if('vision' === col.key || 'thinking' === col.key) {
								if(model['has_' + col.key]) {
									const g = document.createElement('span');
									g.className = 'cerb-icons cerb-icon-' + ('vision' === col.key ? 'eye-open' : 'brain');
									td.appendChild(g);
								}

							} else {
								const tier = (model.ratings || {})[col.key];

								if(tier) {
									td.textContent = tier;
								} else {
									td.className += ' cerb-u-fgg-4';
									td.textContent = '\u2014';
								}
							}

							tr.appendChild(td);
						});

						tbody.appendChild(tr);
					});

					table.appendChild(tbody);

					previewResult.style.display = 'block';
					previewResult.appendChild(table);

					// Built in JS, so the delegated binding never sees these -- bind explicitly, and re-run
					// the preview when an edit lands so the table reflects it.
					$(previewResult).find('.cerb-peek-trigger')
						.cerbPeekTrigger()
						.on('cerb-peek-saved cerb-peek-deleted', function(e) {
							e.stopPropagation();
							previewButton.click();
						});

				}, { error: function() {
					previewButton.disabled = false;
					previewIcon.classList.remove('cerb-u-anim-spin');
					previewResult.className = 'cerb-ui-panel cerb-ui-panel--alert';
					previewResult.style.display = 'block';
					previewResult.textContent = 'The request failed.';
				}});
			});
		}

		if(window.CerbUI && CerbUI.SearchQuery) {
			$popup.find('.cerb-ui-searchquery[data-cerb-searchquery]').each(function() {
				const sq = new CerbUI.SearchQuery(this, {
					onAutocomplete: CerbUI.SearchQuery.queryFieldSource('agent_model'),
					context: 'agent_model',
				});
				const acBtn = this.querySelector('[data-action=autocomplete]');
				if(acBtn) acBtn.addEventListener('click', () => sq.openAutocomplete());
			});
		}

		// The models editor autocompletes from the live `agent_model` records (built server-side per popup) via
		// the same helper `llm.agent: model:` uses -- so what's offered here is exactly what that command accepts,
		// and a model added later shows up with no change to this form.
		if(window.CerbUI && CerbUI.KataEditor) {
			const modelsEl = $frm.find('textarea[name=models_kata]')[0];

			if(modelsEl) {
				new CerbUI.KataEditor(modelsEl, {
					minLines: 4,
					maxLines: 24,
					onAutocomplete: CerbUI.KataEditor.kataFieldSource(modelsAutocomplete)
				});
			}
		}

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete-prompt').click(Devblocks.callbackPeekEditDeletePrompt);
		$popup.find('button.delete-cancel').click(Devblocks.callbackPeekEditDeleteCancel);
	});
});
</script>
