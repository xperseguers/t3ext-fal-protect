# FAL Protect

This extension protects everything within `/fileadmin/` or other relative storages based on associated folder and file
restrictions (visibility,  user groups and dates of publication):

![Protecting a folder and a few individual files][overview]

[overview]: https://raw.githubusercontent.com/xperseguers/t3ext-fal-protect/main/Documentation/Images/overview.png "Protecting a folder and a few individual files"

Unlike other similar extensions securing the File Abstraction Layer (FAL) of TYPO3, this extension aims at making it
straightforward to block direct access to your sensitive assets.

No need to configure anything, just install and enable as usual, block direct access at the server level (Apache/Nginx
see below) and... that's it!

Our motto? [KISS](https://en.wikipedia.org/wiki/KISS_principle)!

## Installation (Apache)

Edit file `.htaccess` to read:

```
RewriteCond %{REQUEST_URI} !/fileadmin/_processed_/.*$
RewriteRule ^fileadmin/.*$ %{ENV:CWD}index.php [QSA,L]
```

**BEWARE:** Be sure to add this rule before any other related rule.

## Installation (Nginx)

Edit your `server` block to read:

```
location / {
    rewrite ^/fileadmin/(?!(_processed_/)) /index.php last;

    # snip
}
```

or, if that better fits your setup, like that:

```
location ~ /fileadmin/(?!(_processed_/)) {
    rewrite ^(.+)$ /index.php last;
}
```

## Why 404 instead of 403 by default?

In case you try to access a restricted file and do not have the right to do so, the logical HTTP status code to use
_should be_ either a `403 Forbidden` (or possibly a `401 Unauthorized`) but by doing so, you make it clear for a
malicious user that the resource exists but is not accessible.

We prefer, by default, to issue a `404 Not Found` but you can freely choose to issue a `403 Forbidden` in the extension
settings. This is particularly useful if you plan to redirect to a login page when a user tries to access a restricted
resource.

## Complete documentation

A more complete documentation can be found on https://docs.typo3.org/p/causal/fal-protect/main/en-us/.
