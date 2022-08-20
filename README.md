
Sympa module for AlternC
========================

This package is an extension for [AlternC hosting software control panel](https://alternc.com) to manage sympa-based mailing and discussion lists virtual robots.

The lists themselves are managed by listmasters on the wwsympa web interface.

it allows AlternC users to create / manage / destroy their mailing lists installed on their own domain name using postfix and sympa on any AlternC 3.X+

It currently manages only the virtual robot creation and destruction for any domain installed on the server, as a result, there is no quota as of now, just a 0 or 1 quota saying whether a user can manage sympa robots on its account or not.
MX must be managed locally to allow sympa to receive email for that domain.


