{*
	One scope's fields -- the agent's defaults, or one surface's overrides.

	ONE partial for both, because two would be two descriptions of one form and they would drift the first time
	a field was added. `surface` is '' for the global scope; everything that differs between the two is a branch
	on it, and the difference is almost all PLACEHOLDER: a surface field left blank gives you whatever the agent
	brings globally, so showing that as the placeholder answers "what happens if I leave this empty?" in the one
	place someone is already looking.

	Every control posts on its own -- `Cerb\Agent\Config::fromForm()` reads them back. Nothing here writes a
	shared model, so nothing has to be kept in sync with anything.

	Params:
	  agent_scope   Config::describeScopeForForm() output
	  prefix        the field-name prefix (`agent`, or `agent[components][<key>]`)
	  surface       the surface key, or '' for the global scope
	  surface_meta  the catalog entry, for the Description placeholder
	  has_namespaces  whether this install registers any `cerb` CLI namespaces at all
	  uid           a unique suffix for element ids
*}
{$inherits = ($surface != '')}


<div class="cerb-ui-form" data-cerb-agent-scope="{$surface}">
	{if $inherits}
		{* What this agent is FOR here, in one line -- the launcher's tooltip in a pane, the second line of its
		   row in the command bar. The only field in this tab aimed at a PERSON rather than at the model, which
		   is why it sits first.

		   Per-SURFACE only, and it inherits nothing: the answer genuinely differs by where the agent runs, and
		   an agent-wide line would be a value you could author and never see.

		   The placeholder is the surface's `tagline`, which is not decoration -- it is literally what the
		   launcher SHOWS while this field is blank (`Launchers::getKata()`). So the empty state is honest
		   rather than a hint at a format. *}
		<div class="cerb-ui-form--field" data-cerb-agent-field="description">
			<label class="cerb-ui-form--label">Description</label>
			<input type="text" name="{$prefix}[description]" value="{$agent_scope.description}" spellcheck="false" placeholder="{$surface_meta.tagline}">
			<div class="cerb-ui-form--hint">One line, shown to whoever picks this agent here. Leave it empty and the launcher uses the surface's own.</div>
		</div>
	{/if}

	{* "Custom Instructions", not "System prompt": the system prompt is the whole concatenation -- the surface's
	   own role, the tool inventory, pointers at the mounted volumes -- and this is only the part the author
	   adds. Naming it after the whole thing invites rewriting what Cerb already said. *}
	<div class="cerb-ui-form--field" data-cerb-agent-field="system_prompt" data-placeholder-empty="{if $inherits}Nothing extra here.{else}Nothing extra.{/if}">
		<label class="cerb-ui-form--label">Custom Instructions</label>
		<textarea name="{$prefix}[system_prompt]" rows="{if $inherits}4{else}6{/if}" spellcheck="false" placeholder="{if $agent_scope.global.system_prompt}{$agent_scope.global.system_prompt}{elseif $inherits}Nothing extra here.{else}Nothing extra.{/if}">{$agent_scope.system_prompt}</textarea>
		<div class="cerb-ui-form--hint">{if $inherits}Optional. Added after the agent's own instructions when it runs here.{else}Optional. Cerb already tells the agent what each surface is and what its tools do; filesystems and tools add their own. This is only what you want to add on top.{/if}</div>
	</div>

	<div class="cerb-ui-form--field" data-cerb-agent-field="models_query" data-placeholder-empty="{if $inherits}Any available model{else}rating.privacy:&gt;=3{/if}">
		<label class="cerb-ui-form--label">Models</label>
		<textarea name="{$prefix}[models_query]" rows="1" spellcheck="false" data-cerb-agent-models-query placeholder="{if $agent_scope.global.models_query}{$agent_scope.global.models_query}{elseif $inherits}Any available model{else}rating.privacy:&gt;=3{/if}">{$agent_scope.models_query}</textarea>
		<div class="cerb-ui-form--hint">{if $inherits}Replaces the agent's query here. Leave empty to use it.{else}Which agent models this agent may use. Leave empty for any available model.{/if}</div>
	</div>

	{* Every mount is READ-ONLY. A mount holds nothing per-mount, so a chip holds everything there is to hold.
	   An `at:` or a `mode:` somebody authored by hand still rides through untouched. *}
	<div class="cerb-ui-form--field" data-cerb-agent-field="mounts" data-placeholder-empty="{'common.search'|devblocks_translate|capitalize}">
		<label class="cerb-ui-form--label">Filesystems</label>
		<div class="cerb-ui-record-chooser" data-cerb-agent-chooser
			data-context="cerb.contexts.agent.filesystem"
			data-name="{$prefix}[mount_ids]"
			data-multiple="1"
			data-empty-icon="folder"
			data-item-icon="folder"
			data-create="1"
			data-placeholder="{'common.search'|devblocks_translate|capitalize}">
			{foreach from=$agent_scope.mounts.records item=mount_ref}
				<li data-context-id="{$mount_ref.id}" data-label="{$mount_ref.label}" data-icon-name="folder"></li>
			{/foreach}
		</div>
		{include file="devblocks:cerberusweb.core::workers/_agent_scope_raw.tpl" raw=$agent_scope.mounts.raw name="{$prefix}[mount_keys][]"}
		<div class="cerb-ui-form--hint">{if $inherits}Mounted here in addition to the agent's own. Read-only.{else}Volumes the agent can browse and read, everywhere it runs. Read-only.{/if}</div>
	</div>

	{* "Custom" because every surface already contributes its own built-in tools -- reading the editor, writing
	   a field, running a search. These are the ones you add. *}
	<div class="cerb-ui-form--field" data-cerb-agent-field="tools" data-placeholder-empty="{'common.search'|devblocks_translate|capitalize}">
		<label class="cerb-ui-form--label">Custom Tools</label>
		<div class="cerb-ui-record-chooser" data-cerb-agent-chooser
			data-context="cerb.contexts.agent.tool"
			data-name="{$prefix}[tool_ids]"
			data-multiple="1"
			data-empty-icon="wrench"
			data-item-icon="wrench"
			data-create="1"
			data-query="status:[available,unlisted]"
			data-placeholder="{'common.search'|devblocks_translate|capitalize}">
			{foreach from=$agent_scope.tools.records item=tool_ref}
				<li data-context-id="{$tool_ref.id}" data-label="{$tool_ref.label}" data-icon-name="{if $tool_ref.icon}{$tool_ref.icon}{else}wrench{/if}"></li>
			{/foreach}
		</div>
		{include file="devblocks:cerberusweb.core::workers/_agent_scope_raw.tpl" raw=$agent_scope.tools.raw name="{$prefix}[tool_keys][]"}
		<div class="cerb-ui-form--hint">{if $inherits}Available here in addition to the agent's own.{else}Tools the agent can call, on top of what each surface already gives it. Each one describes itself to the model.{/if}</div>
	</div>

	{* GRANTS, not switches -- which is why this is a picker and not a row of checkboxes. A surface merges its
	   terminal block onto the agent's and a merge can only add, so a namespace the agent already has is not
	   offered as something to pick: it arrives as a GHOST tile instead, so you can see what this surface is
	   adding to without being handed a control that can't do anything. A checkbox would imply otherwise. *}
	{if !$has_namespaces}
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Terminal</label>
			<div class="cerb-ui-form--hint">No terminal commands are registered.</div>
		</div>
	{else}
		<div class="cerb-ui-form--field" data-cerb-agent-field="terminal" data-placeholder-empty="Add a command">
			<label class="cerb-ui-form--label">Terminal</label>
			<div class="cerb-ui-value-picker" data-cerb-agent-value-picker data-name="{$prefix}[terminal]" data-ghosts="{$agent_scope.global.terminal}">
				{foreach from=$agent_scope.terminal key=namespace item=namespace_state}
					<label data-icon="console" title="{$namespace_state.summary}"><input type="checkbox" name="{$prefix}[terminal][]" value="{$namespace}"{if $namespace_state.on} checked="checked"{/if}> {$namespace}</label>
				{/foreach}
			</div>
			<div class="cerb-ui-form--hint">{if $inherits}Added to what the agent's command line already offers here. A surface can grant more, never less.{else}What the `cerb` command line can report about this install. Read-only reflection -- an agent looks a name up instead of recalling one that may not exist here.{/if}</div>
		</div>
	{/if}

	<div class="cerb-ui-form--field" data-cerb-agent-field="automation" data-placeholder-empty="{'common.search'|devblocks_translate|capitalize}">
		<label class="cerb-ui-form--label">Interaction</label>
		<div class="cerb-ui-record-chooser" data-cerb-agent-chooser
			data-context="cerb.contexts.automation"
			data-name="{$prefix}[automation_id]"
			data-empty-icon="bot-message"
			data-item-icon="bot-message"
			data-query="trigger:cerb.trigger.interaction.worker.agent"
			data-placeholder="{'common.search'|devblocks_translate|capitalize}">
			{if $agent_scope.automation.label}
				<li data-context-id="{$agent_scope.automation.id}" data-label="{$agent_scope.automation.label}" data-icon-name="bot-message"></li>
			{/if}
		</div>
		<div class="cerb-ui-form--hint">{if $inherits}Runs here instead of the agent's own.{else}The chat this agent runs. Leave empty for the one Cerb ships.{/if}</div>
	</div>
</div>
