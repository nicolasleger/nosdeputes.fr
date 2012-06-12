#!/bin/bash

mkdir -p pjl ppl ppr rap ta out

for file in `perl download_docs.pl`; do
  echo $file
  file2=`echo $file | sed 's/^\(pjl\|ppl\|ppr\|rap\|ta\)\//out\//'`
  perl parse_metas.pl $file > $file2
done;


