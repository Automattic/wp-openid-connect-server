# Contributing

## Running locally
Start local site with:

```shell
wp-env start
```

Change permalink structure:

```shell
wp-env run cli "wp rewrite structure '/%postname%'"
```

The site is now available at http://localhost:8888.
