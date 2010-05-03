
PHP =   $(wildcard *.php)
XML =   $(PHP:%.php=%.xml)

.PHONY: all

%.xml: %.php
	@echo "Highlighting $<..."
	@./highlight $< > $@

xml: $(XML)

all: xml
