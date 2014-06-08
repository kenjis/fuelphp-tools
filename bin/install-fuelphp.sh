#!/bin/sh

# FuelPHP Install Script
#
# @author     Kenji Suzuki <https://github.com/kenjis>
# @copyright  2011 Kenji Suzuki
# @license    MIT License http://www.opensource.org/licenses/mit-license.php
# @link       https://github.com/kenjis/fuelphp-tools

if [ $# -lt 2 ]; then
  echo "Install FuelPHP and Create Application Repository"
  echo " usage: $0 branch folder"
  echo "    eg: $0 1.1/develop todo"
  exit;
fi

branch="$1"
dir="$2"

git clone -b "$branch" --recursive git://github.com/fuel/fuel.git "$dir"

cd "$dir"
rm -rf .git .gitmodules *.md docs

git init

git submodule add git://github.com/fuel/core.git   fuel/core/
git submodule add git://github.com/fuel/oil.git    fuel/packages/oil
git submodule add git://github.com/fuel/parser.git fuel/packages/parser
git submodule add git://github.com/fuel/email.git  fuel/packages/email
git submodule add git://github.com/fuel/auth.git   fuel/packages/auth
git submodule add git://github.com/fuel/orm.git    fuel/packages/orm

git submodule foreach git checkout "$branch"
git submodule foreach git pull origin "$branch"

git add .

git commit -m "FuelPHP $branch Initial Commit"

php oil refine install
