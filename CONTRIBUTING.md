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


## Release
Plugin releases are automatically created through a GitHub Action, which is executed whenever a tag of the form `vX.Y.Z` is pushed. You can create and push a tag as follows.

First find the latest tag:

```shell
# Fetch tags from origin
git fetch

# Returns the latest tag, e.g. v0.1.0
git describe --tags --abbrev=0
```

Then create and push a tag that increments the latest one as per [Semantic Versioning](https://semver.org/):

```shell
git tag v0.1.1
git push origin v0.1.1
```

The [GitHub Action](https://github.com/Automattic/wp-openid-connect-server/actions) will launch automatically, and when completed will have created a **draft release** for the tag that was pushed. You should then edit that release to provide a meaningful title and description (ideally including a [changelog](https://keepachangelog.com/en/1.0.0/)), then publish the release.
