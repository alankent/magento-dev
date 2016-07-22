#!/bin/sh

set -x

# Clear out some caches 
rm -rf /magento/var/{cache,page_cache,session,view_preprocessed}

# Turn off the config cache - it is caching the host/port causing problems
# if you use browsersync and the default port.
magento cache:disable config

# Redeploy to pick up new files, compile styles, watch for file changes.
cd /gulp
gulp deploy
gulp styles
gulp docs
gulp dev
