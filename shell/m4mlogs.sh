#!/bin/bash
case "$#" in
   2)
     apikey="$1"
     op="$2"
     ;;
   3)
     apikey="$1"
     op="$2"
     id="$3"
     ;;
   *)
     echo $"Usage: $0  apikey option [id]"
     exit 1
esac
batches=$(grep -l $2_.*$id ../var/log/*.Request.log | cut -d'/' -f3 $a | cut -d'.' -f1)
allresponses=""
allrequests=""
for a in ${batches};
do
	echo $"Downloading response for batch $a"
	allresponses="$allresponses $(php getMailchimpResponse.php $apikey $a)"
	allrequests="$allrequests ../var/log/$a.Request.log"
done;
resp=$(tar cvzf responses.tgz $allresponses)
req=$(tar cvzf requests.tgz $allrequests)
resp=$(rm -f $allresponses)