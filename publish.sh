#!/bin/bash
set -e

rm niwhiboutik.zip || true
git add . || true
git commit -m 'Publishing...' || true
git push || true
npm version patch || true

# Get version from package.json
echo "Getting version from package.json"
VERSION=$(node -p "require('./package.json').version")
echo "Version: $VERSION"

# Update version in plugin file
echo "Updating version in plugin file"
php -r "file_put_contents('niwhiboutik.php', preg_replace('/Version: (.*)/', 'Version: $VERSION', file_get_contents('niwhiboutik.php')));"

yarn build
zip -r niwhiboutik.zip \
    build/ \
    lang/ \
    lib/ \
    niwhiboutik.php \
    vendor/ \
    niwhiboutik-es_ES.mo \
    niwhiboutik-es_ES.po \
    niwhiboutik-de_DE.mo \
    niwhiboutik-de_DE.po \
    .import_status.yml

git add .
git commit
git push
