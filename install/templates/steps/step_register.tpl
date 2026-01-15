<div class="success-box">
	<div class="icon icon-success">
		{call name="icon" icon="circle-check" size=64}
	</div>

	<h2>Installation Complete</h2>

	<p>Cerb has been successfully installed and configured.</p>

	<div class="alert alert-warning mb-3">
		<span class="icon">{call name="icon" icon="triangle-alert" size=20}</span>
		<div class="content">
			<strong>Security Notice:</strong> Delete the <code>install</code> directory before going to production.
		</div>
	</div>

	<p>
		You are now running in <strong>testing mode</strong> with full functionality
		and no time limit for a single seat.
	</p>

	<p class="text-muted">
		Once you <a href="https://cerb.ai/pricing/site/" target="_blank" rel="noopener">purchase a license</a>,
		you can install it from <strong>Setup &raquo; Configure &raquo; License</strong>.
	</p>

	<a href="{devblocks_url}c=login{/devblocks_url}" class="login-link">
		Log in and get started
		{call name="icon" icon="arrow-right" size=20}
	</a>
</div>
