#!/usr/bin/make -f
#
# A simple preprocessor for CSS files
#
# Anewt, Almost No Effort Web Toolkit
# Copyright (C) 2006  Wouter Bolsterlee <uws@xs4all.nl>
#
# This Makefile acts as a simple preprocessor for CSS files. It uses the
# C preprocessor (cpp) to do the actual work. Using this file allows you
# to use variables (eg. aliases for color names, lengths, sizes, or sets
# of common properties) in your CSS files.
#
# Since cpp and CSS both use the # character for different purposes,
# simple substitution (with a very unlikely string) will be done before
# actually invoking cpp. The % character is used to call cpp directives,
# so #define and #include become %define and %include, respectively.
# 
# Sample input:
# 
#   %define MY_SHADE_OF_RED  rgb(196,   0,  12)
#   %define MY_SHADE_OF_BLUE rgb( 10,  42, 240)
#
#   html {
#     background-color: MY_SHADE_OF_RED;
#     color: MY_SHADE_OF_BLUE;
#   }
#
# Sample output from the above sample input:
#
#   html {
#     background-color: rgb(196,   0,  12)
#     color: rgb( 10,  42, 240)
#   }
#
# Usage:
#
# (1) Create files with a .csst (CSS Template) extension. The
#     corresponding .css files will be generated automatically,
#     overwriting the existing .css file with the same name. This file
#     may contain defines, includes, and so on.
#
# (2) Symlink or copy this file as "Makefile" into the directory
#     containing the .csst and .css files. Then run make:
#       $ ln -s /path/to/anewt/scripts/css-cpp.make Makefile
#       $ make css
#
#     Alternatively, call make with a parameter:
#       $ make -f /path/to/anewt/scripts/css-cpp.make css
#
#     Or just execute this file from the right directory:
#       $ /path/to/anewt/scripts/css-cpp.make css
#
#     If your own project also uses a Makefile, you can include this
#     file. To do that, put the following line into your own Makefile:
#       include /path/to/css-cpp.make
#
#     Then you have your own targets depend on the targets in this file:
#       all: css
#       clean: clean-css
#
# (3) Enjoy!
#
#
# If you think the stuff outlined above is evil, just don't use it and
# don't complain. Bug reports are welcome :)
# 


# which programs to use
CHMOD = chmod
SED =   sed


# wildcard matching
CSST = $(wildcard *.csst)
CSS  = $(CSST:%.csst=%.css)


# targets
.PHONY: css clean-css

# The sequence @_@_@ is pretty unlikely, don't you think?
%.css: %.csst
	@printf "Generating %s from %s\n" $@ $<
	@$(SED) -e 's/^#/@_@_@#/' -e 's/^%/#/' $< \
	|cpp -P -C -Wall - \
	|sed -e 's/^@_@_@#/#/' \
	> $@
	@-$(CHMOD) --reference=$< $@ 2>/dev/null

css: $(CSS)

clean-css:
	@printf "Removing generated CSS file: %s\n" "$(CSS)"
	@$(RM) $(CSS)
