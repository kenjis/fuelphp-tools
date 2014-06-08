#!/bin/sh

# Update FuelPHP repositories
#
# @author     Kenji Suzuki <https://github.com/kenjis>
# @copyright  2011 Kenji Suzuki
# @license    MIT License http://www.opensource.org/licenses/mit-license.php
# @link       https://github.com/kenjis/fuelphp-tools

#branch="1.7/master"
branch="1.8/develop"

git fetch origin
git checkout "$branch"
git merge "origin/$branch"

git submodule foreach git fetch origin
git submodule foreach git checkout "$branch"
git submodule foreach git merge "origin/$branch"
