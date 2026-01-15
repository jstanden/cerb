<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Installing Cerb {$smarty.const.APP_VERSION}</title>
	{include file="includes/cerb.css.tpl"}
</head>
<body>
{include file="includes/icons.tpl"}

<div class="installer-container">
	<header class="installer-header">
		{include file="includes/cerb_logo.svg.tpl"}
		<h1>Installing Cerb {$smarty.const.APP_VERSION}</h1>
	</header>

	{* Progress Stepper - Map internal steps to display steps *}
	{* Display steps: 1=Environment, 2=License, 3=Database, 4=Account, 5=Packages, 6=Complete *}
	{if $step <= $smarty.const.STEP_ENVIRONMENT}
		{$display_step = 1}
	{elseif $step == $smarty.const.STEP_LICENSE}
		{$display_step = 2}
	{elseif $step >= $smarty.const.STEP_DATABASE && $step <= $smarty.const.STEP_INIT_DB}
		{$display_step = 3}
	{elseif $step == $smarty.const.STEP_DEFAULTS}
		{$display_step = 4}
	{elseif $step == $smarty.const.STEP_PACKAGES}
		{$display_step = 5}
	{elseif $step >= $smarty.const.STEP_REGISTER}
		{$display_step = 6}
	{else}
		{$display_step = 1}
	{/if}

	<nav class="installer-progress">
		<div class="progress-step {if $display_step > 1}completed{elseif $display_step == 1}active{/if}">
			<div class="progress-step-circle">
				{if $display_step > 1}{call name="icon" icon="check" size=18}{else}1{/if}
			</div>
			<span class="progress-step-label">Environment</span>
		</div>
		<div class="progress-step {if $display_step > 2}completed{elseif $display_step == 2}active{/if}">
			<div class="progress-step-circle">
				{if $display_step > 2}{call name="icon" icon="check" size=18}{else}2{/if}
			</div>
			<span class="progress-step-label">License</span>
		</div>
		<div class="progress-step {if $display_step > 3}completed{elseif $display_step == 3}active{/if}">
			<div class="progress-step-circle">
				{if $display_step > 3}{call name="icon" icon="check" size=18}{else}3{/if}
			</div>
			<span class="progress-step-label">Database</span>
		</div>
		<div class="progress-step {if $display_step > 4}completed{elseif $display_step == 4}active{/if}">
			<div class="progress-step-circle">
				{if $display_step > 4}{call name="icon" icon="check" size=18}{else}4{/if}
			</div>
			<span class="progress-step-label">Account</span>
		</div>
		<div class="progress-step {if $display_step > 5}completed{elseif $display_step == 5}active{/if}">
			<div class="progress-step-circle">
				{if $display_step > 5}{call name="icon" icon="check" size=18}{else}5{/if}
			</div>
			<span class="progress-step-label">Packages</span>
		</div>
		<div class="progress-step {if $display_step >= 6}active{/if}">
			<div class="progress-step-circle">
				{if $display_step >= 6}{call name="icon" icon="check" size=18}{else}6{/if}
			</div>
			<span class="progress-step-label">Complete</span>
		</div>
	</nav>

	{if !empty($template)}
		<main class="installer-card">
			{include file=$template}
		</main>
	{/if}
</div>

</body>
</html>
