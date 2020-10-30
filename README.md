# FAL Protect

This extension protects everything within `/fileadmin/` based on associated file restrictions.

## Installation (Apache)

Edit file `.htaccess` to read:

```
RewriteCond %{REQUEST_URI} !/fileadmin/_processed_/.*$
RewriteRule ^fileadmin/.*$ %{ENV:CWD}index.php [QSA,L]
```

**BEWARE:** Be sure to add this rule before any other related rule.

## Testing

If you try to access a non-existing file within `/fileadmin/`, you should get a 403 error.
