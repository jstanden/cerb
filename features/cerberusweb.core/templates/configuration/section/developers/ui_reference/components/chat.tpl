	<div class="cerb-uiref-component" id="chat">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-bot"></span>Chat</div>

		<p class="cerb-u-text-muted" style="margin:0 0 1em;">A full-width transcript of conversational turns (<code>--user</code> / <code>--assistant</code>, plus <code>--tool</code> / <code>--system</code>) for rendering an AI/LLM session. <code>--user</code> turns are filled (a subtle gray band); <code>--assistant</code> turns are left <b>unfilled</b> because agent output is usually Markdown-formatted and a background would clash — the flow still reads as alternating bands. A tool call collapses into a quiet <code>&lt;details&gt;</code> with its result rendered inline under the call (<code>--tool-result-label</code> + <code>&lt;pre&gt;</code>). Reuses <a href="#avatar">cerb-ui-avatar</a> and the shared <code>.commentBodyHtml</code> body rules.</p>

		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-chat" style="max-width:520px;">
					<div class="cerb-ui-chat--turn cerb-ui-chat--user">
						<div class="cerb-ui-chat--role"><span class="cerb-icons cerb-icon-user"></span> user</div>
						<div class="cerb-ui-chat--body">How do I reset my password?</div>
					</div>
					<div class="cerb-ui-chat--turn cerb-ui-chat--assistant">
						<div class="cerb-ui-chat--role"><span class="cerb-icons cerb-icon-bot"></span> assistant</div>
						<div class="cerb-ui-chat--body">You can reset it from Settings, then Security.</div>
						<details class="cerb-ui-chat--tool" open>
							<summary><b><span class="cerb-icons cerb-icon-hammer"></span> search_docs</b></summary>
							<pre>query: "reset password"</pre>
							<div class="cerb-ui-chat--tool-result-label">Result</div>
							<pre>Password reset lives under Settings &rarr; Security.</pre>
						</details>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-chat"&gt;
    &lt;!-- A user turn: filled (gray band); role eyebrow + body --&gt;
    &lt;div class="cerb-ui-chat--turn cerb-ui-chat--user"&gt;
        &lt;div class="cerb-ui-chat--role"&gt;&lt;span class="cerb-icons cerb-icon-user"&gt;&lt;/span&gt; user&lt;/div&gt;
        &lt;div class="cerb-ui-chat--body"&gt;How do I reset my password?&lt;/div&gt;
    &lt;/div&gt;

    &lt;!-- An assistant turn: unfilled (Markdown body); a tool call collapses into &lt;details&gt; with the result inline --&gt;
    &lt;div class="cerb-ui-chat--turn cerb-ui-chat--assistant"&gt;
        &lt;div class="cerb-ui-chat--role"&gt;&lt;span class="cerb-icons cerb-icon-bot"&gt;&lt;/span&gt; assistant&lt;/div&gt;
        &lt;div class="cerb-ui-chat--body"&gt;You can reset it from Settings, then Security.&lt;/div&gt;
        &lt;details class="cerb-ui-chat--tool" open&gt;
            &lt;summary&gt;&lt;b&gt;&lt;span class="cerb-icons cerb-icon-hammer"&gt;&lt;/span&gt; search_docs&lt;/b&gt;&lt;/summary&gt;
            &lt;pre&gt;query: "reset password"&lt;/pre&gt;
            &lt;div class="cerb-ui-chat--tool-result-label"&gt;Result&lt;/div&gt;
            &lt;pre&gt;Password reset lives under Settings.&lt;/pre&gt;
        &lt;/details&gt;
    &lt;/div&gt;
&lt;/div&gt;</pre>
			</div>
		</div>
	</div>
