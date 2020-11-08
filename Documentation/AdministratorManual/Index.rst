.. include:: /Includes.rst.txt
.. _admin-manual:

====================
Administrator manual
====================

As an administrator, we make your life as lightweight as possible, unlike other
similar extensions. The only point you need to tackle is to prevent direct
access to :file:`/fileadmin/` at the web server level.

.. note::

   You are naturally free to adapt the configuration to leave
   :file:`/fileadmin/` free to access but enable access check only for a given
   list of subdirectories. If so, your web server will bypass any possible
   restrictions the users defined to files and directories you choose to never
   ever protect.


.. _admin-manual-apache:

Installation (Apache)
=====================

Edit file :file:`.htaccess` (or your virtual host) to read:

.. code-block:: apache

   RewriteCond %{REQUEST_URI} !/fileadmin/_processed_/.*$
   RewriteRule ^fileadmin/.*$ %{ENV:CWD}index.php [QSA,L]

**BEWARE:** Be sure to add this rule before any other related rule.


.. _admin-manual-nginx:

Installation (Nginx)
====================

Edit your `server` block to read:

.. code-block:: nginx

   location / {
      rewrite ^/fileadmin/(?!(_processed_/)) /index.php last;

      # snip
   }

or, if that better fits your setup, like that:

.. code-block:: nginx

   location ~ /fileadmin/(?!(_processed_/)) {
      rewrite ^(.+)$ /index.php last;
   }
