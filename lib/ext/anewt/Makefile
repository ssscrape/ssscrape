.PHONY: all doc test permissions force-run

all:
	@echo
	@echo "Usage:"
	@echo
	@echo "    make doc            Generate Doxygen documentation from the"
	@echo "                        source code"
	@echo "    make tags           Create a tags file from the source"
	@echo "    make permissions    Correctly set all file permissions"
	@echo "    make test           Execute all files named '*.test.php'"
	@echo

force-run:

doc:
	$(MAKE) -C doc doc

permissions:
	@# world readable
	@find . -type d |xargs chmod 755
	@find . -type f |xargs chmod 644
	@# make scripts executable
	@find scripts -type f |xargs chmod +x
	@chmod +x doc/manual/examples/highlight

tags: force-run
	@echo -n "Generating tags file..."
	@ctags -R --php-kinds=+cdf-v --languages=php .
	@echo " done."


test:
	for file in $$(ls */*.test.php); do \
		cd $$(dirname $$file); \
		php $$(basename $$file); \
		cd ..; \
	done

