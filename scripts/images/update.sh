#!/bin/bash
# cd into each subdir and update images
# only for updating, run "php clone_link.php" before using this

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null && pwd )"
cd "$DIR"
for dir in */ ; do
    echo "$dir"
    cd "$dir"
    if [ -d ".git" ]; then
        git reset --hard HEAD
        git pull
        cd "$DIR"
    fi
done