.. include:: ../Includes.rst.txt
.. _file-delivery:

File delivery
=============

Once a file passed the access check, it still has to be transferred to the
client. By default, the extension does that in PHP. This chapter explains why
this may become a bottleneck on busy websites and how to hand the transfer over
to the web server instead.


.. _file-delivery-why:

Why PHP delivery can become a bottleneck
----------------------------------------

With the rewrite rules from the :ref:`administrator manual <admin-manual>`,
every request to :file:`/fileadmin/` (except for the "_processed_" folder) is
answered by TYPO3. This includes images embedded in pages and files without any
restriction.

When PHP streams a file, the PHP worker (e.g., a PHP-FPM child process) remains
busy for the *whole transfer*, not only for the access check. As soon as the
client reads slower than the server writes, PHP waits for the network. A 20 MB
PDF downloaded by a mobile client at 1 Mbit/s keeps one worker busy for about
160 seconds, while using almost no CPU.

The number of workers is limited (``pm.max_children``). A few slow downloads
are enough to make regular page requests wait in the PHP-FPM listen queue. This
does not show up as CPU or memory load: watch the *active processes* and the
*listen queue* on the PHP-FPM status page instead.

Long transfers may also be terminated by limits such as PHP-FPM's
``request_terminate_timeout``, regardless of PHP's own time limit.


.. _file-delivery-modes:

Delivery modes
--------------

The extension configuration option :guilabel:`File delivery` (``delivery``)
selects who transfers the file:

``php`` (default)
   The file is streamed by PHP. This works everywhere and needs no web server
   configuration.

``x-sendfile``
   PHP answers with an empty body and an ``X-Sendfile`` header holding the
   absolute path of the file. The web server then transfers the file itself,
   e.g., Apache with `mod_xsendfile`_. The PHP worker is released as soon as
   the access check is done.

``x-accel-redirect``
   Same principle for Nginx, using its built-in ``X-Accel-Redirect`` header and
   an internal location. No additional module is needed.

In every mode, the access check stays in TYPO3 and the response carries a
``Last-Modified`` header. Conditional requests (``If-Modified-Since``) for an
unchanged file are answered with ``304 Not Modified`` by the extension itself,
before any transfer is handed over. With ``x-sendfile`` and
``x-accel-redirect``, the web server also takes care of byte ranges
(``206 Partial Content``), so interrupted downloads can be resumed.

.. warning::

   Only switch to ``x-sendfile`` or ``x-accel-redirect`` once the web server
   is configured accordingly. Otherwise, the header is not processed and
   clients receive an empty file.


.. _file-delivery-apache:

Apache with mod_xsendfile
-------------------------

Install and enable the module, e.g., on Debian or Ubuntu:

.. code-block:: bash

   apt install libapache2-mod-xsendfile
   a2enmod xsendfile

Then allow it in your virtual host, for the directories of your storages:

.. code-block:: apache

   XSendFile On
   XSendFilePath /var/www/example.com/public/fileadmin

Please note:

- ``XSendFilePath`` is only allowed in the server or virtual host
  configuration, not in :file:`.htaccess`.
- The extension sends the resolved path of the file. mod_xsendfile only serves
  it if it lies within ``XSendFilePath`` after resolving symbolic links as
  well. If :file:`fileadmin/` is a symbolic link, e.g., to the :file:`shared/`
  directory of a deployment tool, ``XSendFilePath`` must name the *resolved*
  directory, not the path of the current release.
- Headers set by TYPO3 (``Content-Type``, ``Cache-Control``, ...) are kept.

.. _mod_xsendfile: https://tn123.org/mod_xsendfile/


.. _file-delivery-nginx:

Nginx with X-Accel-Redirect
---------------------------

Add an internal location that maps to the public directory of your TYPO3
installation. Its path must match the extension configuration option
:guilabel:`Internal location` (``delivery_internal_location``, default:
``/_fal_protect_internal/``):

.. code-block:: nginx

   location ^~ /_fal_protect_internal/ {
     internal;
     alias /var/www/example.com/public/;
   }

The ``internal`` directive ensures that clients cannot request this location
directly. Files of storages outside of the public directory are still streamed
by PHP.

.. tip::

   Nginx evaluates regular expression locations before plain prefix locations.
   If your server block already contains regular expression locations matching
   :file:`/fileadmin/` (e.g., to set headers for PDF files), these win over the
   rewrite to :file:`index.php` and files are served without any access check.
   A ``^~`` prefix location avoids that:

   .. code-block:: nginx

      location ^~ /fileadmin/ {
        location ^~ /fileadmin/_processed_/ {
        }
        rewrite ^ /index.php last;
      }


.. _file-delivery-verify:

Verifying the setup
-------------------

First, make sure a protected file can be downloaded completely and that the
response contains neither ``X-Sendfile`` nor ``X-Accel-Redirect``, as the web
server consumes these headers:

.. code-block:: bash

   curl -sI https://example.com/fileadmin/some/document.pdf

Then check that the PHP worker is released. Download a large file at a low
rate and watch the PHP-FPM status page in the meantime:

.. code-block:: bash

   curl -o /dev/null --limit-rate 50k https://example.com/fileadmin/some/large.pdf

With PHP delivery, one worker stays active until the download is finished.
With ``x-sendfile`` or ``x-accel-redirect``, it is idle again right away.
