sass --sourcemap=none -t expanded cerb.scss cerb.css \
	&& cp cerb.css ../../../../../features/cerberusweb.core/resources/css/ \
	&& echo Wrote cerb.css
