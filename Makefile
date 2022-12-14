#!/usr/bin/make -f
# ----------------------------------------------------------------------
# AlternC - Web Hosting System
# Copyright (C) 2000-2022 by the AlternC Development Team.
# https://alternc.org/
# ----------------------------------------------------------------------
# LICENSE
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License (GPL)
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# To read the license please visit http://www.gnu.org/copyleft/gpl.html
# ----------------------------------------------------------------------
# Purpose of file: Global Makefile 
# ----------------------------------------------------------------------
MAJOR=$(shell sed -ne 's/^[^(]*(\([^)]*\)).*/\1/;1p' debian/changelog)
REV=$(shell env LANG=C svn info --non-interactive | awk '/^Revision:/ { print $$2 }')
VERSION="${MAJOR}~svn${REV}"
export VERSION

build:

install: 
	cp -r bureau/* $(DESTDIR)/usr/share/alternc/panel/
# 1999 is alterncpanel (TODO: ask Debian for a static uid/gid ?)
	chown 1999:1999 -R $(DESTDIR)/usr/share/alternc/panel/
# install system scripts:
	install -m 0755 src/update_sympa.sh \
		$(DESTDIR)/usr/lib/alternc/
	install -m 0644 sympa.sql \
		$(DESTDIR)/usr/share/alternc/install/
	install -m 0755 init/wwsympa init/sympasoap $(DESTDIR)/etc/init.d/
	install -m 0644 sympa-robot.conf $(DESTDIR)/etc/alternc/templates/apache2/
	install -m 0644 list_aliases.tt2 $(DESTDIR)/etc/sympa/
	install -m 750 alternc-sympa-install $(DESTDIR)/usr/lib/alternc/install.d/

	rm -f $(DESTDIR)/usr/share/alternc/panel/locales/Makefile

