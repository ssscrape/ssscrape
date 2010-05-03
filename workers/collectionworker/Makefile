
.PHONY: .force-run all clean clean-pyc


.force-run:
	@# do nothing


all: tags


clean: clean-pyc


clean-pyc:
	$(info Removing byte-compiled Python files...)
	find . -name '*.py[co]' -print0 |xargs -0 $(RM)


tags: .force-run
	$(info Creating tags file...)
	ctags --recurse=yes --languages=python

