#!/usr/bin/make -f
#
# A simple management script for MySQL databases
#
# Anewt, Almost No Effort Web Toolkit
# Copyright (C) 2007  Wouter Bolsterlee <uws@xs4all.nl>
#
#
# IMPORTANT NOTICE: This file contains some convenience macros to handle MySQL
# databases during development. You should REALLY know what you're doing,
# otherwise you may LOSE ALL YOUR DATA! Use at your own risk, no guarantees :)
#
#
# To use this file, create a file database.conf with the lines below. Remove
# whitespace and add the appropriate settings.
#
#   hostname=localhost
#   database=develdb
#   username=john
#   password=doe
#
# Optionally, you may provide a custom port number:
#
#   port=1234
#
# Typically you want to symlink this file into a database/ directory in your
# project directory:
# 
#   mkdir database
#   cd database
#   ln -s /path/to/anewt/scripts/database-mysql.make Makefile
#
# Now you can write your database schema (only DROP TABLE IF EXISTS and CREATE
# TABLE statements) in a file called database-schema.sql. Once you've done
# that, you can use the make targets in this file. Use "make usage" for more
# information.
#
# An example of typical usage would be:
#
#   - Create a SQL schema in database-schema.sql. Be careful with the SQL
#     formatting since this Makefile uses grep and string substitution to
#     find out table names (for dumping and dropping of tables). Important
#     things to take into account:
#     - Always include a drop table line before a CREATE TABLE line, like this:
#         DROP TABLE IF EXISTS `foo`;
#     - Always put a newline after the
#         CREATE TABLE `foo` (
#       part of your schema definition.
#   - Execute "make create"
#   - Insert some data, test your application
#   - Add new tables to the schema
#   - Execute "make dump" to make a backup of the data
#   - Execute "make create" to install the new schema
#   - Execute "make restore" to restore your data. Note that this only works if
#     the newly defined schema matches the data in the dump file (so column
#     additions with default values will work, but column renaming does not).
#     You may of course change the database-data-dump.sql file manually to fit
#     the new schema.
#


#
# Variables
#

# Default database configuration, schema, and data dump files
DBCONFFILE=database.conf
DBSCHEMAFILE=database-schema.sql
DBDUMPFILE=database-data-dump.sql

# Extract database settings from the database configuration file
DBHOSTNAME:=$(shell grep ^hostname= $(DBCONFFILE) |cut -d= -f2-)
DBPORT:=$(shell grep ^port= $(DBCONFFILE) |cut -d= -f2-)
DBUSERNAME:=$(shell grep ^username= $(DBCONFFILE) |cut -d= -f2-)
DBPASSWORD:=$(shell grep ^password= $(DBCONFFILE) |cut -d= -f2-)
DBDATABASE:=$(shell grep ^database= $(DBCONFFILE) |cut -d= -f2-)

# Extract the table names from the schema. First extract the lines containing
# table names, then remove the surrounding SQL statements and quotation, and
# finally add single quotes around each extracted table name.
DBTABLES:=${shell \
	grep -i '^create table' $(DBSCHEMAFILE) \
	| sed \
	-e 's/create table //i' \
	-e 's/ *( *//' \
	-e 's/ LIKE .*//' \
	-e 's/`//g' \
	-e "s/^/'/" \
	-e "s/\$$/'/" \
	}


#
# Targets
#

# Phony target listing (should list all targets)
.PHONY: all usage shell create dump restore

# Default target
all: usage

# Print usage information
usage:
	@echo 'Usage: make COMMAND'
	@echo
	@echo 'Command overview:'
	@echo
	@echo '   make shell           Opens a MySQL shell to your database. This is'
	@echo '                        convenient, because you do not have to specify'
	@echo '                        the hostname and account details.'
	@echo
	@echo '   make create          Executes the database-schema.sql script. Note that'
	@echo '                        this DELETES ALL DATA FROM YOUR DATABASE, so use with'
	@echo '                        care.'
	@echo
	@echo '   make drop            Executes only the DROP TABLE and DROP VIEW lines'
	@echo '                        from the database-schema.sql script. Note that this'
	@echo '                        DELETES ALL DATA FROM YOUR DATABASE, so use with care.'
	@echo
	@echo '   make dump            dumps all database content (no schemas) to'
	@echo '                        database-data-dump.sql. Only tables in the schema file'
	@echo '                        will be dumped.'
	@echo
	@echo '   make restore         Executes a previously dumped database-data-dump.sql.'
	@echo '                        This is useful to restore data, but note that your'
	@echo '                        data may not survive schema changes. This'
	@echo '                        usually only works if the database is empty.'
	@echo

# Open a MySQL shell for the configured database
shell:
	@mysql \
	--host='$(DBHOSTNAME)' \
	--port='$(DBPORT)' \
	--user='$(DBUSERNAME)' \
	--password='$(DBPASSWORD)' \
	$(DBDATABASE)

# Create the database based on the schema
create:
	@echo "Creating schema..."
	@mysql \
	--host='$(DBHOSTNAME)' \
	--port='$(DBPORT)' \
	--user='$(DBUSERNAME)' \
	--password='$(DBPASSWORD)' \
	$(DBDATABASE) \
	< '$(DBSCHEMAFILE)'

# Drop all tables defined in the schema
drop:
	@echo "Dropping tables..."
	@grep -i -E '^DROP (TABLE|VIEW)' '$(DBSCHEMAFILE)' \
	|tac \
	| mysql \
	--host='$(DBHOSTNAME)' \
	--port='$(DBPORT)' \
	--user='$(DBUSERNAME)' \
	--password='$(DBPASSWORD)' \
	$(DBDATABASE)

# Make a data dump of all tables defined in the schema
dump:
	@echo "Dumping data..."
	@mysqldump \
	--host='$(DBHOSTNAME)' \
	--port='$(DBPORT)' \
	--user='$(DBUSERNAME)' \
	--password='$(DBPASSWORD)' \
	--no-create-info \
	--quote-names \
	--complete-insert \
	--extended-insert=0 \
	$(DBDATABASE) \
	$(DBTABLES) \
	> '$(DBDUMPFILE)'

# Restore a previously made data dump
restore:
	@echo "Restoring data..."
	@mysql \
	--host='$(DBHOSTNAME)' \
	--port='$(DBPORT)' \
	--user='$(DBUSERNAME)' \
	--password='$(DBPASSWORD)' \
	$(DBDATABASE) \
	< '$(DBDUMPFILE)'
