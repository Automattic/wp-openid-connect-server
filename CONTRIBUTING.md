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

## Releasing
Releases are automatically created through a GitHub Action, which is executed whenever a tag of the form `vX.Y.Z` is pushed.

You can create and push a tag with:

```shell
# Make sure you consider https://semver.org to decide which version you're issuing.
# Note the version must not be prefixed with a "v", e.g. it should be 1.2.3, not v1.2.3.  
bin/prepare-release.sh 1.2.3
```

Running the above script will trigger the [GitHub Action](https://github.com/Automattic/wp-openid-connect-server/actions), which when completed will have created a **draft release** for the tag that was pushed. You should then edit that release to provide a meaningful title and description (ideally including a [changelog](https://keepachangelog.com/en/1.0.0/)), then publish the release through the GitHub UI.
