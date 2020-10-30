# FAL Protect

This extension protects everything within `/fileadmin/` based on associated file restrictions (visibility and user
groups).

Goal is that you have nothing to configure (at least at this point). Just install and enable the extension, block direct
access and that's it!

## Installation (Apache)

Edit file `.htaccess` to read:

```
RewriteCond %{REQUEST_URI} !/fileadmin/_processed_/.*$
RewriteRule ^fileadmin/.*$ %{ENV:CWD}index.php [QSA,L]
```

**BEWARE:** Be sure to add this rule before any other related rule.

## Installation (Nginx)

Edit your `location /` block to read:

```
location / {
    rewrite ^/fileadmin/(?!(_processed_/)) /index.php last;

    # snip
}
```

## Why 404 instead of 403?

In case you try to access a restricted file and do not have the right to do so, the logical HTTP status code to use
_should be_ either a `403 Forbidden` (or possibly a `401 Unauthorized`) but by doing so, you make it clear for a
malicious user that the resource exists but is not accessible.

We prefer, at least for the time being (see ideas for the future below) to issue a `404 Not Found` instead.

## Ideas for the future

* Instead of denying access altogether if the user is not authenticated at all, it could be useful to redirect to a
  login page instead.
