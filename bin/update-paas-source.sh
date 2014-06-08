#!/bin/sh

# @author     Kenji Suzuki <https://github.com/kenjis>
# @copyright  2011 Kenji Suzuki
# @license    MIT License http://www.opensource.org/licenses/mit-license.php
# @link       https://github.com/kenjis/fuelphp-tools

dest="__EDIT_HERE__"


cd `dirname $0`
source=${PWD##*/}

cd ..
rsync -av --exclude ".git/" --exclude .gitignore --exclude .gitmodules \
  --exclude .buildpath --exclude .project --exclude .settings \
  --exclude docs/ --exclude fuel/app/logs/ \
  "$source/" "$dest"

cd "$dest"
rm "$0"
