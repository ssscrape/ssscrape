#!/usr/bin/make -f
#
# A simple way to add proper i18n support to PHP web applications.
#
# Anewt, Almost No Effort Web Toolkit
# Copyright (C) 2006--2008  Wouter Bolsterlee <uws@xs4all.nl>
#

CONFFILE=i18n.conf
NAME=$(shell grep ^translation-domain= $(CONFFILE) |cut -d= -f2)
DIRS=$(shell grep ^source-directories= $(CONFFILE) |cut -d= -f2)
INSTALLDIR=$(shell grep ^install-directory= $(CONFFILE) |cut -d= -f2)
ENCODING=$(shell grep ^encoding= $(CONFFILE) |cut -d= -f2)

.PHONY: all force-run update-po

POFILES = $(wildcard *.po)
GMOFILES = $(POFILES:%.po=%.gmo)

XGETTEXT_ARGS="--keyword=Q_ --keyword=N_"

%.gmo: %.po
	@echo -n "$<: "
	@msgfmt -v -o $@ $<

all: $(GMOFILES)

force-run:

POTFILES.in: force-run
	@printf "Populating %s...\n" $@
	@echo -n '' > $@
	@test -z $(ENCODING) || echo "[encoding: $(ENCODING)]" > $@
	@(cd .. && find $(DIRS) -name '*.php') >> $@

update-po: POTFILES.in
	@printf "Updating PO template...\n"
	@XGETTEXT_ARGS=$(XGETTEXT_ARGS) intltool-update --pot --gettext-package=$(NAME)
	@for file in $(POFILES); do \
		echo; \
		echo -n Updating "$$file"; \
		XGETTEXT_ARGS=$(XGETTEXT_ARGS) intltool-update -g $(NAME) `basename "$$file" .po`; \
		echo; \
	done;

install: $(GMOFILES)
	@for file in $(GMOFILES); do \
		echo -n Installing "$$file to "; \
		dir=../$(INSTALLDIR)/`basename "$$file" .gmo`/LC_MESSAGES; \
		target=$$dir/$(NAME).mo; \
		echo $$target; \
		mkdir -p $$dir; \
		install -m 644 $$file $$target; \
	done;

clean:
	$(RM) POTFILES.in $(NAME).pot $(GMOFILES)
