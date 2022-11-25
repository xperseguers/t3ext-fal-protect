.. include:: ../Includes.rst.txt
.. _introduction:

Introduction
============

.. _what-it-does:

What does it do?
----------------

This extension protects everything within :file:`/fileadmin/` based on
associated folder and file restrictions (visibility, user groups and dates of
publication):

.. image:: ../Images/overview.png
   :alt: Protecting a folder and a few individual files
   :align: center
   :class: with-border with-shadow

Unlike other :ref:`similar extensions <alternatives>` securing the File
Abstraction Layer (FAL) of TYPO3, this extension aims at making it
straightforward to block direct access to your sensitive assets **and** to keep
the exact same URL as if your :file:`/fileadmin/` would not be protected; this
is thus totally transparent from a user perspective.

No need to configure anything, just install and enable as usual, block direct
access at the server level (Apache/Nginx see below) and... that's it!

Our motto? `KISS <https://en.wikipedia.org/wiki/KISS_principle>`__!


.. _how-does-it-work:

How does it work?
-----------------

The idea is to block direct access at the server level so that your Apache or
Nginx web server delegates the handling of static assets to a small script
within this extension which ensures any file and folder restrictions are
enforced.

By design, the "_processed_" folder (:file:`/fileadmin/_processed_/`) is not
protected and its content (thumbnails or resized/cropped images) is always
freely accessible.

There is another design choice that is worth mentioning: access checks are
bypassed in Frontend while you are authenticated in the Backend as a TYPO3
administrator. So if you want to double check your security measures are working
properly, be sure to use another browser/session or ensure you are not currently
authenticated as an administrator in the TYPO3 Backend.

NOTE: Since version > 1.2.0 all ProcessedFiles are resolved to the original FAL
resource. As result you can also protect the "_processed_" folder, if the
resolution of the original file rights is correct for your purpose.


.. _alternatives:

Differences with similar extensions
-----------------------------------

We have found two similar extensions with their own differences to this
extensions:

1. `fal_securedownload <https://extensions.typo3.org/extension/fal_securedownload/>`__:

   - changes the download URL (using the ``eID`` concept from TYPO3 core, non-public storages);
   - only file links from non-public (protected) storages go through security proxy
   - provides Frontend-related components (a File tree JS component);
   - is able to keep track of a count of downloads.

2. `secure_downloads <https://extensions.typo3.org/extension/secure_downloads/>`__:

   - requires relatively complex configuration at the server level and as
     administrator in TYPO3 Backend;
   - changes the download URL;
   - supports more advanced use-cases like one-time download link.
