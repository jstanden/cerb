<style>
@charset "UTF-8";

:root {
	--cerb-installer-bg: rgb(28, 28, 32);
	--cerb-installer-card-bg: rgb(38, 38, 44);
	--cerb-installer-card-border: rgb(55, 55, 65);
	--cerb-installer-text: rgb(220, 220, 225);
	--cerb-installer-text-muted: rgb(140, 140, 150);
	--cerb-installer-text-heading: rgb(255, 255, 255);
	--cerb-installer-accent: rgb(85, 130, 230);
	--cerb-installer-accent-hover: rgb(100, 145, 245);
	--cerb-installer-success: rgb(60, 190, 60);
	--cerb-installer-error: rgb(230, 70, 70);
	--cerb-installer-warning: rgb(240, 180, 40);
	--cerb-installer-input-bg: rgb(24, 24, 28);
	--cerb-installer-input-border: rgb(70, 70, 80);
	--cerb-installer-input-focus: rgb(85, 130, 230);
	--cerb-installer-button-gradient-from: rgb(70, 70, 80);
	--cerb-installer-button-gradient-to: rgb(50, 50, 58);
	--cerb-installer-progress-bg: rgb(50, 50, 58);
	--cerb-installer-progress-line: rgb(70, 70, 80);
}

*, *::before, *::after {
	box-sizing: border-box;
}

html {
	font-size: 15px;
}

html, body {
	margin: 0;
	padding: 0;
	background-color: var(--cerb-installer-bg);
	min-height: 100vh;
}

body {
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
	font-size: 1rem;
	line-height: 1.5;
	color: var(--cerb-installer-text);
	padding: 2rem;
}

/* Container */
.installer-container {
	max-width: 800px;
	margin: 0 auto;
}

/* Header */
.installer-header {
	text-align: center;
	margin-bottom: 2rem;
}

.installer-header svg {
	max-width: 240px;
	height: auto;
	margin-bottom: 1rem;
}

.installer-header h1 {
	font-size: 1.5rem;
	font-weight: 600;
	color: var(--cerb-installer-text-heading);
	margin: 0;
}

/* Progress Stepper */
.installer-progress {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	margin-bottom: 2.5rem;
	padding: 0 1rem;
	position: relative;
}

.installer-progress::before {
	content: "";
	position: absolute;
	top: 18px;
	left: 50px;
	right: 50px;
	height: 2px;
	background: var(--cerb-installer-progress-line);
	z-index: 0;
}

.progress-step {
	display: flex;
	flex-direction: column;
	align-items: center;
	position: relative;
	z-index: 1;
	flex: 1;
	max-width: 100px;
}

.progress-step-circle {
	width: 36px;
	height: 36px;
	border-radius: 50%;
	background: var(--cerb-installer-progress-bg);
	border: 2px solid var(--cerb-installer-progress-line);
	display: flex;
	align-items: center;
	justify-content: center;
	font-weight: 600;
	font-size: 0.875rem;
	color: var(--cerb-installer-text-muted);
	margin-bottom: 0.5rem;
	transition: all 0.2s ease;
}

.progress-step.completed .progress-step-circle {
	background: var(--cerb-installer-success);
	border-color: var(--cerb-installer-success);
	color: white;
}

.progress-step.active .progress-step-circle {
	background: var(--cerb-installer-accent);
	border-color: var(--cerb-installer-accent);
	color: white;
	box-shadow: 0 0 0 4px rgba(85, 130, 230, 0.25);
}

.progress-step-label {
	font-size: 0.7rem;
	color: var(--cerb-installer-text-muted);
	text-align: center;
	white-space: nowrap;
}

.progress-step.active .progress-step-label,
.progress-step.completed .progress-step-label {
	color: var(--cerb-installer-text);
}

/* Card */
.installer-card {
	background: var(--cerb-installer-card-bg);
	border: 1px solid var(--cerb-installer-card-border);
	border-radius: 12px;
	padding: 2rem;
	margin-bottom: 1.5rem;
}

.installer-card h2 {
	font-size: 1.35rem;
	font-weight: 600;
	color: var(--cerb-installer-text-heading);
	margin: 0 0 1rem 0;
	padding-bottom: 0.75rem;
	border-bottom: 1px solid var(--cerb-installer-card-border);
}

.installer-card h2:not(:first-child) {
	margin-top: 1.5rem;
}

.installer-card h3 {
	font-size: 1rem;
	font-weight: 600;
	color: var(--cerb-installer-accent);
	margin: 1.25rem 0 0.75rem 0;
}

.installer-card h3:first-child {
	margin-top: 0;
}

.installer-card h3 + .form-row,
.installer-card h3 + .form-group {
	margin-top: 0;
}

/* Summary box */
.check-summary {
	display: flex;
	align-items: center;
	gap: 0.75rem;
	padding: 1rem 1.25rem;
	background: var(--cerb-installer-bg);
	border-radius: 8px;
	margin-bottom: 1.5rem;
}

.check-summary.success {
	border-left: 4px solid var(--cerb-installer-success);
}

.check-summary.error {
	border-left: 4px solid var(--cerb-installer-error);
}

.check-summary .icon {
	flex-shrink: 0;
}

.check-summary .text {
	font-weight: 500;
}

/* Check sections */
.check-section {
	margin-bottom: 1rem;
	border: 1px solid var(--cerb-installer-card-border);
	border-radius: 8px;
	overflow: hidden;
}

.check-section:last-child {
	margin-bottom: 0;
}

.check-section-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 0.875rem 1rem;
	background: var(--cerb-installer-bg);
	cursor: pointer;
	user-select: none;
	transition: background-color 0.15s ease;
}

.check-section-header:hover {
	background: rgba(255, 255, 255, 0.03);
}

.check-section-header .title {
	display: flex;
	align-items: center;
	gap: 0.625rem;
	font-weight: 500;
}

.check-section-header .status {
	display: flex;
	align-items: center;
	gap: 0.5rem;
	font-size: 0.875rem;
	color: var(--cerb-installer-text-muted);
}

.check-section-header .chevron {
	transition: transform 0.2s ease;
}

.check-section.open .check-section-header .chevron {
	transform: rotate(180deg);
}

.check-section-content {
	display: none;
	padding: 0.5rem 1rem 1rem 1rem;
}

.check-section.open .check-section-content {
	display: block;
}

/* Check items */
.check-item {
	display: flex;
	align-items: center;
	gap: 0.75rem;
	padding: 0.625rem 0;
	border-bottom: 1px solid var(--cerb-installer-card-border);
}

.check-item:last-child {
	border-bottom: none;
}

.check-item .icon {
	flex-shrink: 0;
}

.check-item .label {
	flex: 1;
}

.check-item .status-text {
	font-size: 0.875rem;
}

.check-item .status-text.success {
	color: var(--cerb-installer-success);
}

.check-item .status-text.error {
	color: var(--cerb-installer-error);
}

.check-item .status-text.warning {
	color: var(--cerb-installer-warning);
}

/* Standalone check (not in section) */
.check-standalone {
	display: flex;
	align-items: center;
	gap: 0.75rem;
	padding: 1rem 1.25rem;
	background: var(--cerb-installer-bg);
	border-radius: 8px;
	margin-bottom: 1rem;
}

.check-standalone .icon {
	flex-shrink: 0;
}

.check-standalone .content {
	flex: 1;
}

.check-standalone .label {
	font-weight: 500;
	margin-bottom: 0.125rem;
}

.check-standalone .detail {
	font-size: 0.875rem;
	color: var(--cerb-installer-text-muted);
}

.check-standalone .detail.success {
	color: var(--cerb-installer-success);
}

.check-standalone .detail.error {
	color: var(--cerb-installer-error);
}

/* Forms */
form {
	margin: 0;
}

fieldset {
	border: 1px solid var(--cerb-installer-card-border);
	border-radius: 8px;
	padding: 1.25rem;
	margin: 0 0 1.5rem 0;
}

fieldset:last-of-type {
	margin-bottom: 0;
}

legend {
	font-weight: 600;
	color: var(--cerb-installer-accent);
	padding: 0 0.5rem;
	font-size: 0.95rem;
}

.form-group {
	margin-bottom: 1rem;
}

.form-group:last-child {
	margin-bottom: 0;
}

.form-group label {
	display: block;
	font-weight: 500;
	margin-bottom: 0.5rem;
	color: var(--cerb-installer-text);
}

.form-group .hint {
	font-size: 0.8125rem;
	color: var(--cerb-installer-text-muted);
	margin-top: 0.375rem;
}

.form-row {
	display: flex;
	gap: 1rem;
}

.form-row .form-group {
	flex: 1;
}

input[type="text"],
input[type="password"],
input[type="email"],
select,
textarea {
	width: 100%;
	padding: 0.625rem 0.875rem;
	font-size: 1rem;
	font-family: inherit;
	color: var(--cerb-installer-text);
	background-color: var(--cerb-installer-input-bg);
	border: 1px solid var(--cerb-installer-input-border);
	border-radius: 6px;
	outline: none;
	transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

input[type="text"]:focus,
input[type="password"]:focus,
input[type="email"]:focus,
select:focus,
textarea:focus {
	border-color: var(--cerb-installer-input-focus);
	box-shadow: 0 0 0 3px rgba(85, 130, 230, 0.15);
}

input::placeholder {
	color: var(--cerb-installer-text-muted);
	opacity: 0.7;
}

select {
	appearance: none;
	background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='rgb(140,140,150)' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
	background-repeat: no-repeat;
	background-position: right 0.75rem center;
	padding-right: 2.5rem;
}

/* Buttons */
button, .button {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 0.5rem;
	padding: 0.75rem 1.5rem;
	font-size: 1rem;
	font-weight: 600;
	font-family: inherit;
	color: var(--cerb-installer-text);
	background: linear-gradient(to bottom, var(--cerb-installer-button-gradient-from), var(--cerb-installer-button-gradient-to));
	border: 1px solid var(--cerb-installer-card-border);
	border-radius: 8px;
	cursor: pointer;
	text-decoration: none;
	transition: all 0.15s ease;
}

button:hover, .button:hover {
	background: linear-gradient(to bottom, var(--cerb-installer-accent), var(--cerb-installer-accent-hover));
	border-color: var(--cerb-installer-accent);
	color: white;
}

button[type="submit"], .button-primary {
	background: linear-gradient(to bottom, var(--cerb-installer-accent), rgb(70, 115, 210));
	border-color: var(--cerb-installer-accent);
	color: white;
}

button[type="submit"]:hover, .button-primary:hover {
	background: linear-gradient(to bottom, var(--cerb-installer-accent-hover), var(--cerb-installer-accent));
}

button svg, .button svg {
	width: 18px;
	height: 18px;
}

.button-row {
	display: flex;
	gap: 1rem;
	margin-top: 1.5rem;
}

/* Alerts */
.alert {
	display: flex;
	align-items: flex-start;
	gap: 0.75rem;
	padding: 1rem 1.25rem;
	border-radius: 8px;
	margin-bottom: 1.5rem;
}

.alert .icon {
	flex-shrink: 0;
	margin-top: 0.125rem;
}

.alert .content {
	flex: 1;
}

.alert-error {
	background-color: rgba(230, 70, 70, 0.1);
	border: 1px solid rgba(230, 70, 70, 0.3);
	color: var(--cerb-installer-error);
}

.alert-warning {
	background-color: rgba(240, 180, 40, 0.1);
	border: 1px solid rgba(240, 180, 40, 0.3);
	color: var(--cerb-installer-warning);
}

.alert-success {
	background-color: rgba(60, 190, 60, 0.1);
	border: 1px solid rgba(60, 190, 60, 0.3);
	color: var(--cerb-installer-success);
}

.alert-info {
	background-color: rgba(85, 130, 230, 0.1);
	border: 1px solid rgba(85, 130, 230, 0.3);
	color: var(--cerb-installer-accent);
}

.alert ul {
	margin: 0.5rem 0 0 0;
	padding-left: 1.25rem;
}

.alert li {
	margin-bottom: 0.25rem;
}

/* Package selection cards */
.package-options {
	display: flex;
	flex-direction: column;
	gap: 1rem;
}

.package-option {
	display: flex;
	align-items: flex-start;
	gap: 1rem;
	padding: 1.25rem;
	background: var(--cerb-installer-bg);
	border: 2px solid var(--cerb-installer-card-border);
	border-radius: 10px;
	cursor: pointer;
	transition: all 0.15s ease;
}

.package-option:hover {
	border-color: var(--cerb-installer-accent);
}

.package-option.selected {
	border-color: var(--cerb-installer-accent);
	background: rgba(85, 130, 230, 0.05);
}

.package-option input[type="radio"] {
	width: 20px;
	height: 20px;
	margin: 0.125rem 0 0 0;
	accent-color: var(--cerb-installer-accent);
}

.package-option .content {
	flex: 1;
}

.package-option .title {
	font-weight: 600;
	font-size: 1.05rem;
	margin-bottom: 0.375rem;
	color: var(--cerb-installer-text-heading);
}

.package-option .description {
	font-size: 0.9rem;
	color: var(--cerb-installer-text-muted);
}

/* License box */
.license-box {
	max-height: 400px;
	overflow-y: auto;
	padding: 1.5rem;
	background: var(--cerb-installer-bg);
	border: 1px solid var(--cerb-installer-card-border);
	border-radius: 8px;
	font-size: 0.9rem;
	line-height: 1.6;
	margin-bottom: 1.5rem;
}

.license-box h1 {
	font-size: 1.25rem;
	margin: 0 0 1rem 0;
	color: var(--cerb-installer-text-heading);
}

.license-box h2 {
	font-size: 1rem;
	margin: 1.5rem 0 0.75rem 0;
	padding: 0;
	border: none;
	color: var(--cerb-installer-accent);
}

.license-box p {
	margin: 0 0 1rem 0;
}

.license-box ul {
	margin: 0 0 1rem 0;
	padding-left: 1.5rem;
}

.license-box li {
	margin-bottom: 0.5rem;
}

.license-accept {
	display: flex;
	align-items: center;
	gap: 0.75rem;
	padding: 1rem 1.25rem;
	background: var(--cerb-installer-bg);
	border-radius: 8px;
	margin-bottom: 1.5rem;
	font-style: italic;
	color: var(--cerb-installer-text-muted);
}

/* Success page */
.success-box {
	text-align: center;
	padding: 2rem;
}

.success-box .icon {
	margin-bottom: 1.5rem;
}

.success-box h2 {
	border: none;
	padding: 0;
	margin: 0 0 1rem 0;
}

.success-box p {
	color: var(--cerb-installer-text-muted);
	margin-bottom: 0.75rem;
}

.success-box .login-link {
	display: inline-flex;
	align-items: center;
	gap: 0.5rem;
	margin-top: 1.5rem;
	font-size: 1.125rem;
	font-weight: 600;
	color: var(--cerb-installer-accent);
	text-decoration: none;
}

.success-box .login-link:hover {
	color: var(--cerb-installer-accent-hover);
	text-decoration: underline;
}

/* Redirect/Loading page */
.loading-container {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 3rem;
	text-align: center;
}

.spinner {
	width: 48px;
	height: 48px;
	margin-bottom: 1.5rem;
	animation: spin 1s linear infinite;
}

.spinner circle {
	fill: none;
	stroke: var(--cerb-installer-accent);
	stroke-width: 4;
	stroke-linecap: round;
	stroke-dasharray: 90, 150;
	stroke-dashoffset: 0;
}

@keyframes spin {
	100% {
		transform: rotate(360deg);
	}
}

.loading-text {
	font-size: 1.125rem;
	color: var(--cerb-installer-text-muted);
}

/* Links */
a {
	color: var(--cerb-installer-accent);
	text-decoration: none;
}

a:hover {
	text-decoration: underline;
}

/* Icon colors */
.icon-success {
	color: var(--cerb-installer-success);
}

.icon-error {
	color: var(--cerb-installer-error);
}

.icon-warning {
	color: var(--cerb-installer-warning);
}

.icon-muted {
	color: var(--cerb-installer-text-muted);
}

/* Utility */
.text-muted {
	color: var(--cerb-installer-text-muted);
}

.text-success {
	color: var(--cerb-installer-success);
}

.text-error {
	color: var(--cerb-installer-error);
}

.text-warning {
	color: var(--cerb-installer-warning);
}

.mb-0 { margin-bottom: 0; }
.mb-1 { margin-bottom: 0.5rem; }
.mb-2 { margin-bottom: 1rem; }
.mb-3 { margin-bottom: 1.5rem; }
.mt-0 { margin-top: 0; }
.mt-1 { margin-top: 0.5rem; }
.mt-2 { margin-top: 1rem; }
.mt-3 { margin-top: 1.5rem; }
</style>
