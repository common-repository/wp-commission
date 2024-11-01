#!/bin/bash

tmp_dir=/tmp/wpcommission

rm -rf $tmp_dir
cp -r . $tmp_dir
cd $tmp_dir
rm -rf ./.git
rm release.sh
cd ..
zip -r wpcommission-$(date +%Y%m%d).zip wpcommission
rm -rf wpcommission
