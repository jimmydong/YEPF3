#!/bin/sh
echo '#####################################################'
echo '# require build'
echo '# usage:  ./build.sh [_c] [_a] [debug]'
echo '# eg: ./build.sh index index true'
echo

if [ ! -n "$1" ]; then
  echo "without _c _a !"
  exit
fi
node pre_build.js $1 $2 true 
file="$1/_$2_profile.js"
if [ -f $file ]; then
  rm -f $1/_$2_build.js
  if [ -n $3 ]; then
    node r.js -o $file optimize=none  
  else
    node r.js -o $file
  fi
  ls -l $1/_$2_build.js
  echo Done!
  echo
  echo Rememter to submit build file:  
  echo svn add $1/_$2_build.js
  echo svn commit $1/_$2_build.js -m 'build'
  echo 
else
  echo "Error: Can't find $file "
fi


