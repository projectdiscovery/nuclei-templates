#!/bin/bash

set -uo pipefail

OUTPUT="/tmp/nuclei-result-${GITHUB_SHA}.out"
CHANGED_FILES="$(sed 's/ / -t /g' <<< "${CHANGED_FILES}")"
WEAK=false
COMMENT=""

eval "nuclei -duc -silent -ud ${GITHUB_WORKSPACE} -u ${HONEYPOT_URL} -o ${OUTPUT} -t ${CHANGED_FILES}"

if [[ "$(wc -l < $OUTPUT)" -gt 0 ]]; then
	COMMENT+="**:warning: Weak matcher detected**\n\n"
	COMMENT+="It looks like Nuclei has found some results on the honeypot target.\n\n"
	COMMENT+="To improve the accuracy of these results and avoid any false positives, "
	COMMENT+="please adjust the matchers as needed. "
	COMMENT+="This will help in providing more reliable and precise results.\n\n"
	COMMENT+="| **Template ID** |\n"
	COMMENT+="|--|\n"
	COMMENT+=$(grep -Po "^\\K[[\w_-]+\]" $OUTPUT | sed 's/\[/| /g; s/\]/ |/g' | sed ':a;N;$!ba;s/\n/\\n/g')
	COMMENT+="\n\n"
	COMMENT+="> Ref ${GITHUB_SHA}"

	WEAK=true
fi

echo "weak=${WEAK}" >> $GITHUB_OUTPUT

{
	echo "comment<<EOF"
	echo -e "${COMMENT}"
	echo "EOF"
} >> $GITHUB_OUTPUT