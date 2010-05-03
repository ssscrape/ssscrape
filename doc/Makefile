DOXYGEN=/usr/bin/doxygen

SHELL=/bin/sh
SOURCE_HOME=$(shell pwd)/..
LIBDIR=${SOURCE_HOME}/lib
TMPDIR=${SOURCE_HOME}/doc/tmp


.PHONY: doc preprocess_code copy_code

doc: prepare_code doxygen.conf
	test -x ${DOXYGEN} || ( echo "Error: DX_HOME undefined or ${DOXYGEN} not found" && exit 1 )
	${DOXYGEN} doxygen.conf 
	rm -rf ${TMPDIR}

doxygen.conf: doxygen.conf.template
	cat $< | sed "s:SOURCE_HOME:${SOURCE_HOME}:"  > $@

prepare_code: copy_code
	for pyfile in `find ${TMPDIR} -name \*.py`; do \
		cat $$pyfile | perl -pne "s/^(\s*(\"{3}|\'{3}))/\\1\!/" >tmpfile.py; \
		mv tmpfile.py $$pyfile; \
	done

copy_code:
	rm -rf ${TMPDIR}
	mkdir ${TMPDIR}
	cp -r ${LIBDIR}/* ${TMPDIR}
