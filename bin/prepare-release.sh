set -e

set -e

function error {
  RED='\033[0;31m'
  NONE='\033[0m'
  printf "$RED$1$NONE\n"
  exit 1
}

if [ -z "$1" ]; then
    error "Provide a new version, current version is $(jq '.version' composer.json)"
fi

VERSION=$1
if [[ $VERSION == v* ]]; then
  # Strip leading v.
  VERSION="${VERSION:1}"
fi

RELEASE_BRANCH="release-$VERSION"

CURRENT_BRANCH=$(git rev-parse --symbolic-full-name --abbrev-ref HEAD)
if [[ "$CURRENT_BRANCH" != "main" ]] && [[ "$CURRENT_BRANCH" != "$RELEASE_BRANCH" ]]; then
  error "You must be on branch main"
fi

# Make sure we're up-to-date with origin.
git fetch
git pull --ff-only origin main

# Checkout or create branch for release.
if [[ $(git branch --list "$RELEASE_BRANCH") ]]
then
  git checkout "$RELEASE_BRANCH"
else
  git checkout -b "$RELEASE_BRANCH"
fi

jq ".version = \"$VERSION\"" composer.json > composer.json.tmp
mv composer.json.tmp composer.json
git add composer.json

sed -i"" -e "s/\(Version: \)\(.*\)/\1          $VERSION/g" openid-connect-server.php
sed -i"" -e "s/\(Stable tag: \)\(.*\)/\1$VERSION/g" README.md
rm -f openid-connect-server.php-e README.md-e
git add openid-connect-server.php README.md

# Show diff and ask for confirmation.
git --no-pager diff --cached
printf "\n\n"
read -p "Would you like to commit, push and open a PR for the above diff? [y|n] " yn
case $yn in
	yes|y )
	  echo "Ok, continuing";;
	* )
	  error "Exiting without committing."
esac

# Commit and push.
git commit -m "Release $VERSION"
git push -u origin "$RELEASE_BRANCH"

# Open PR.
LATEST_VERSION_TAG=$(git describe --tags --match "[0-9]*" --abbrev=0 HEAD)
PR_BODY=$(cat <<-EOB
[Commits since $LATEST_VERSION_TAG](https://github.com/Automattic/wp-openid-connect-server/compare/$LATEST_VERSION_TAG...$RELEASE_BRANCH)
EOB
)
gh pr create --draft --base main --label "Prepare Release" --title "Release $VERSION" --body "$PR_BODY" --assignee @me

echo "A Pull Request has been created for Release $VERSION (see URL above)."
echo "The release will automatically be created once the Pull Request is merged."
