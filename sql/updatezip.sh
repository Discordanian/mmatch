#!/bin/sh
wget http://download.geonames.org/export/zip/US.zip
unzip US.zip
sort < US.txt > sorted.txt
echo Number of rows in input file:
wc -l sorted.txt US.txt
#sort of quick trick to ensure that the file is here, and is at least 2M in size
#if it doesn't pass this test, there is probably some problem, but don't want to wipe out zip code table
find -name sorted.txt -mmin -5 -size +2M -exec mysql -D shared_db < updatezip.sql \;
#rm -f US.txt readme.txt sorted.txt
