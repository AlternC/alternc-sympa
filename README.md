
Sympa module for AlternC
========================

This package is an extension for [AlternC hosting software control panel](https://alternc.com) to manage sympa-based mailing and discussion lists virtual robots.

The lists themselves are managed by listmasters on the wwsympa web interface.

it allows AlternC users to create / manage / destroy their mailing lists installed on their own domain name using postfix and sympa on any AlternC 3.X+

It currently manages only the virtual robot creation and destruction for any domain installed on the server, as a result, there is no quota as of now, just a 0 or 1 quota saying whether a user can manage sympa robots on its account or not.
MX must be managed locally to allow sympa to receive email for that domain.


Installation
============

This module depends on sympa, which will be installed as a debian package during the install process.
You should type "enter" to choose the default, for each question the sympa installer will ask you.

Please note that this package suppose that you are using the alternc-nginx-ssl package, and therefore that https is automatically configured properly. As a result, wwsympa vhosts are forcing HTTPS.

Once alternc-sympa is installed, you should change the value from zero to non-zero for the "sympa" quotas of an AlternC account. This will allow this account to use sympa and to add a virtual robot.

Also, if you want to have an administrator account valid on all robots, change the 'listmaster' value in /etc/sympa/sympa/sympa.conf and restart both wwsympa and sympa services.



Technical information
=====================

cgi for wwsympa & sympasoap
---------------------------

Since AlternC is running apache with mpm-itk, it's not easy to make wwsympa works directly using mod_fcgid. We decided to use spawn-fcgi, a simple fcgi daemon runner, to run both wwsympa and sympasoap. Then apache will be able to access those daemon using a unix socket in /run/sympa/*.socket. Therefore, this package is deploying 2 init scripts for sysvinit (will work with systemd too) : /etc/init.d/ wwsympa and sympasoap


