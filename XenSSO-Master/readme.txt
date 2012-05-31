NOTE - For the latest installation instructions always check http://naatan.com/support/threads/installation-instructions.14/

Introduction
------------
XenSSO consists of 2 addons; XenSSO-Master and XenSSO-Slave. The master addon is to be installed on your main XenForo site that is to serve as the authentication "repository" for all your other sites. The Slave addon is to be installed on all other sites. You should NOT install both Master and Slave on the same site.

Master Installation
1. Extract library folder contents to your XenForo library folder, you should end up with a folder structure like: "<base>/library/XenSSO/Master"
2. Install the addon in XenForo by going into the admin panel, then to the "Install Addon" section and then by uploading the "addon-xensso_master.xml" file included with this addon.
3. Go to the XenForo options page and open up the "XenSSO - Master" group
4. Enter a public and private secret key, you can just use a password generator (eg. http://www.pctools.com/guides/password/), it's best to only use alphanumeric characters.
   NOTE: If you already entered secret keys on your XenSSO Slave install use those instead, all servers on your XenForo network should have the same keys configured!
5. Enter a list of domains (each on a new line) that are allowed to use this server for authentication, this would be a list of domains for each of your slave installs. Note: it's best to enter each domain with and without the www variation (ie. enter both mydomain.com as well as www.mydomain.com).
6. Done

Slave Installation
1. Extract library folder contents to your XenForo library folder, you should end up with a folder structure like: "<base>/library/XenSSO/Slave"
2. Extract js folder contents to your XenForo js folder, you should end up with a folder structure like: "<base>/js/xensso/"
3. Install the addon in XF by going into the admin panel, then to the "Install Addon" section and then by uploading the "addon-xensso_slave.xml" file included with this addon.
4. Go to the XenForo options page and open up the "XenSSO - Slave" group
5. For Master URL, enter the URL of the XenForo setup that will serve as the master server. The value should be the same as you've entered for the "Board Url" option on the master server.
6. Enter a public and private secret key, you can just use a password generator (eg. http://www.pctools.com/guides/password/), it's best to only use alphanumeric characters. The public and private keys should not be the same!
   NOTE: If you already entered secret keys on your XenSSO Master install use those instead, all servers on your XenForo network should have the same key configured!
7.  Done

Initial Sync
------------
Upon installing XenSSO for the first time you may want to do an initial sync of your accounts from the slave install to a master, to do this login to the admin control panel on a slave server and go to the Tools section, you should have the entry XenSSO on the left, click on "Synchronise Users" and follow the steps.