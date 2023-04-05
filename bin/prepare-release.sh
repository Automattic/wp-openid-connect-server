set -e

if [ -z "$1" ]; then
    echo "Provide a new version, current version is $(jq '.version' composer.json)"
    exit 1
fi

VERSION=$1

# Remove the "v" character (v1.2.3 to 1.2.3)
if [[ $VERSION == v* ]]; then
    VERSION="${VERSION#v}"
fi

git checkout main
git fetch
git pull origin main

jq ".version = \"$VERSION\"" composer.json > composer.json.tmp
mv composer.json.tmp composer.json
git add composer.json

sed -i"" -e "s/\(Version: \)\(.*\)/\1          $VERSION/g" openid-connect-server.php
sed -i"" -e "s/\(Stable tag: \)\(.*\)/\1$VERSION/g" README.md
rm -f openid-connect-server.php-e README.md-e
git add openid-connect-server.php README.md

git commit -m "Release v$VERSION"
git tag "v$VERSION"
git push --tags origin main

echo "Edit the draft release on GitHub and then commit to SVN manually"
