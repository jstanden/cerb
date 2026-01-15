<h2>Choose Environment</h2>

{if $failed}
<div class="alert alert-error">
	<span class="icon">{call name="icon" icon="circle-x" size=20}</span>
	<div class="content">Some required information was not provided.</div>
</div>
{/if}

<form action="index.php" method="POST">
	<input type="hidden" name="step" value="{$smarty.const.STEP_PACKAGES}">
	<input type="hidden" name="form_submit" value="1">

	<p class="text-muted mb-3">Select how you want Cerb to be configured. You can change these settings later.</p>

	<div class="package-options">
		<label class="package-option {if !$package || $package=="demo"}selected{/if}">
			<input type="radio" name="package" value="demo" {if !$package || $package=="demo"}checked="checked"{/if}>
			<div class="content">
				<div class="title">Demo / Development</div>
				<div class="description">
					Cerb will be configured for demonstration, development, and testing with sample data.
				</div>
			</div>
		</label>

		<label class="package-option {if $package=="standard"}selected{/if}">
			<input type="radio" name="package" value="standard" {if $package=="standard"}checked="checked"{/if}>
			<div class="content">
				<div class="title">Production</div>
				<div class="description">
					Cerb will be configured for real-world use with a clean database.
				</div>
			</div>
		</label>
	</div>

	<div class="button-row">
		<button type="submit">
			Install
			{call name="icon" icon="arrow-right" size=18}
		</button>
	</div>
</form>

<script>
document.querySelectorAll('.package-option').forEach(function(option) {
	option.addEventListener('click', function() {
		document.querySelectorAll('.package-option').forEach(function(o) {
			o.classList.remove('selected');
		});
		this.classList.add('selected');
	});
});

document.querySelector('form').addEventListener('submit', function() {
	var btn = this.querySelector('button[type=submit]');
	btn.disabled = true;
	btn.innerHTML = 'Installing...';
});
</script>
