
AlternC Sympa module development
================================

This module is currently under active development, and all wanted features are not implemented.

* [ ] Basic creation and destruction of sympa virtual robots  (in progress)
* [ ] proper destruction of virtual robots (do we need to destroy properly all the lists?)
* [ ] management of mailing lists itself via AlternC? (creation / destruction) (it's not really needed, but allow us to manage quotas)


Known Bugs: 
-----------

If you delete a domain that has the WEB interface of sympa (not the one that receives the mails) the sympa robot will be "dangling" ... 
We should remove the robot entirely in that case :/ (hook_dom_del_domain)

we don't have the update_sympa.sh cron yet ;) so no robot is really created / deleted

also, please search for "TODO" in the code

If we can't entirely manage the list of lists in a domain via AlternC
we will need to check that there are no conflicts with existing mail box/alias for the same domain
in that case, telling the user seems useful ;) 


