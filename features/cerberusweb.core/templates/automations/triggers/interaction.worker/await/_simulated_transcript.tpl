{* Design-time sample shown ONLY when there's no live transcript to render (empty/nonexistent session_id) — a
   real session with messages renders the actual transcript (llm_transcript.tpl). Mirrors the live conversation
   shape so the builder preview shows what the worker will see: the user turn is the current worker; the agent
   turn a representative default model (Anthropic · Claude Fable 5) since none is configured at design time. *}
{$sample_id = uniqid('sample_transcript_')}
<div class="cerb-form-builder-prompt cerb-form-builder-response-llm-transcript" id="{$sample_id}">
	{if $label}<h6>{$label}</h6>{/if}

	{* Bare markup, enhanced below — CerbUI.AgentTranscript.enhance() builds the chrome (matches the live tpl). *}
	<div class="cerb-ui-agent-transcript cerb-ui-agent-transcript--sample" data-cerb-agent-transcript data-cerb-dom="simulated-transcript">
		{* User turn = the current worker (mirrors the live transcript's worker-identity branch). *}
		<div data-cerb-transcript-turn data-role="user">
			{if is_a($llm_session_user, 'Model_Worker')}
				<span data-cerb-transcript-avatar data-avatar="{$llm_session_user->getName()}" data-avatar-seed="worker:{$llm_session_user->id}" data-avatar-image="{$llm_session_user->getImageUrl()}"></span>
			{else}
				<span data-cerb-transcript-avatar data-avatar-icon="user" data-avatar-seed="user"></span>
			{/if}
			<div data-cerb-transcript-sender>
				{if is_a($llm_session_user, 'Model_Worker')}
					<span class="cerb-ui-agent-transcript--sender-name">{$llm_session_user->getName()}</span>
					{if $llm_session_user->title}<span class="cerb-u-text-muted">{$llm_session_user->title}</span>{/if}
				{else}
					<span class="cerb-ui-agent-transcript--sender-name">User</span>
				{/if}
			</div>
			<div data-cerb-transcript-body><div class="commentBodyHtml">How many open tickets are waiting on me right now?</div></div>
		</div>

		{* Agent turn = a representative model (design-time default; the live session names its real provider). *}
		<div data-cerb-transcript-turn data-role="assistant">
			<span data-cerb-transcript-avatar data-avatar-icon="{$agent_provider_icon|default:'bot'}" data-avatar-seed="agent:sample"{if $agent_provider_color} data-avatar-color="{$agent_provider_color}"{/if}></span>
			<div data-cerb-transcript-sender><span class="cerb-ui-agent-transcript--sender-name">{$agent_model_label|default:'Agent'}</span></div>
			<div data-cerb-transcript-body><div class="commentBodyHtml">You have <strong>3</strong> open tickets assigned to you. The oldest has been waiting about two hours &mdash; want me to open it?</div></div>

			{* Sample thinking + tool use — inside the assistant turn; enhance() lifts them into the sub-thread in
			   author order (thinking, then the tool call it leads to). *}
			<div data-cerb-transcript-thinking>
				<div class="commentBodyHtml">They want their current open workload. I'll search for tickets they own with an open status, oldest first, then summarize the count and how long the oldest has waited.</div>
			</div>
			<div data-cerb-transcript-tool data-tool-name="search_tickets" data-tool-id="toolu_sample01" data-icon="search" data-duration-ms="420" data-summary-active="Searching your open tickets" data-summary-past="Searched your open tickets">
				<textarea data-cerb-transcript-tool-params spellcheck="false">{literal}{"query":"status:open owner:me sort:created"}{/literal}</textarea>
				<pre data-cerb-transcript-tool-result>Found 3 matching tickets.</pre>
			</div>
		</div>
	</div>

	<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
	$(function() {
		// controls:false — --sample's opacity would dim them, and it's a fixed sample. Display modes still
		// mirror the author's config so the preview reflects their chosen view/thinking/tools/expand.
		if(window.CerbUI && CerbUI.AgentTranscript)
			CerbUI.AgentTranscript.enhance($('#{$sample_id}')[0], undefined, {
				controls: false,
				view: '{$view|default:'markdown'|escape:'javascript'}',
				layout: '{$layout|default:'interleaved'|escape:'javascript'}',
				thinking: '{$thinking|default:'summary'|escape:'javascript'}',
				tools: '{$tools|default:'summary'|escape:'javascript'}',
				expand: '{$expand|default:'latest'|escape:'javascript'}'
			});
	});
	</script>

	<div class="cerb-u-text-muted" style="margin-top:0.75em;font-size:0.85em;">
		(Sample transcript &mdash; the live conversation renders here once the session has messages.)
	</div>
</div>
