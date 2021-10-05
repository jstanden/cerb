sass --sourcemap=none -t expanded cerb.scss cerb.css \
	&& mv cerb.css ../../../../../features/cerberusweb.core/resources/css/ \
	&& echo Wrote cerb.css
