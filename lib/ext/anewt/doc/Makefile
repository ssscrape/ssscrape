.PHONY: all doc doxygen manual

all: doc

doc: doxygen manual

doxygen:
	@echo "Generating Doxygen documentation..."
	@(cat doxygen/Doxyfile; echo -n "STRIP_FROM_PATH = "; dirname doxygen) |doxygen -

manual:
	@echo "Generating manual..."
	$(MAKE) -C manual all
