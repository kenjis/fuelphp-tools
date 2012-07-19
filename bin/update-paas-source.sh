#!/bin/sh

dest="__EDIT_HERE__"


cd `dirname $0`
source=${PWD##*/}

cd ..
rsync -av --exclude ".git/" --exclude .gitignore --exclude .gitmodules \
  --exclude "docs/" "$source/" "$dest"

cd "$dest"
rm "$0"

