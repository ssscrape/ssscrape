XMLLINT = xmllint
XSLTPROC = xsltproc

.PHONY: all examples manual api valid clean coverage .force-run


#
# Default target
#

all: examples api manual valid coverage $(HTML)


#
# Targets for building the manual
#

examples:
	@printf "Highlighting examples...\n" $@
	@$(MAKE) -s -C examples

manual: manual.xml examples
	@printf "Generating manual...\n" $@
	@printf "Copying assets to output directory...\n" $@
	@cp -a -f assets output/
	@printf "Generating pages...\n" $@
	@$(XSLTPROC) --param 'outputdir' '"output"' manual-to-html.xsl $<

api:
	@for INFILE in ../doxygen/xml/class*.xml; do \
		printf "Processing %s\n" $$INFILE; \
		$(XSLTPROC) \
			--param 'outputdir' '"output"' \
			doxygen.xsl \
			$$INFILE; \
	done

valid:
	@printf "Validating generated files...\n" $@
	@find output -maxdepth 1 -name '*.html' |xargs -n1 $(XMLLINT) --noout --valid


#
# Documentation coverage targets
#

coverage: all-classes.txt all-functions.txt documented-classes.txt undocumented-classes.txt
	@printf "Documentation coverage: %d out of %d classes documented (%d left)\n" \
		`wc -l < documented-classes.txt` \
		`wc -l < all-classes.txt` \
		`wc -l < undocumented-classes.txt`

all-classes.txt: ../doxygen/xml/index.xml .force-run
	@printf "Generating %s...\n" $@
	@$(XSLTPROC) --param 'kind' '"class"' doxygen-list-kind.xsl $< |sort > $@

all-functions.txt: ../doxygen/xml/index.xml .force-run
	@printf "Generating %s...\n" $@
	@$(XSLTPROC) --param 'kind' '"function"' doxygen-list-kind.xsl $< |sort > $@

documented-classes.txt: .force-run
	@printf "Generating %s...\n" $@
	@(find ../../ -name module.doc.xml |xargs -n1 $(XSLTPROC) list-documented-classes.xsl) \
		|sort > $@

undocumented-classes.txt: all-classes.txt documented-classes.txt
	@printf "Generating %s...\n" $@
	@diff -u $^ |grep '^-[^-]' |sed -e 's/^-//' > $@


#
# Other targets
#

clean:
	@printf "Removing generated HTML...\n" $@
	@$(RM) output/*.html


#
# Helper targets
#

.force-run:
	@# do nothing

