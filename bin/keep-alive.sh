#!/bin/sh

# check for command line parameters 
if [ $# = 0 ];
then
  echo "usage: $0 <path> daemon|scheduler"
  exit 0
fi

# set some vars
SSSCRAPEPATH=$1
SSSCRAPEWHAT=$2

if [ "$SSSCRAPEWHAT" = "" ];
then
  SSSCRAPEWHAT=daemon
fi

SSSCRAPEPIDFILE="$SSSCRAPEPATH/ssscrape-$SSSCRAPEWHAT.pid"

# can we get the pid?
if [ ! -s $SSSCRAPEPIDFILE ];
then
  SSSCRAPEPID=0
else
  SSSCRAPEPID=`cat $SSSCRAPEPIDFILE 2>/dev/null` 
fi

# if we can get the pid, check to see if it's actually a ssscrape process
if [ $SSSCRAPEPID -gt 0 ];
then
  if grep -qs "$SSSCRAPEPATH/bin/ssscrape-$SSSCRAPEWHAT" /proc/$SSSCRAPEPID/cmdline ;
  then
    exit 0
  fi
  SSSCRAPEPID=0 
fi

# if the pid is 0, then start the server
if [ $SSSCRAPEPID -eq 0 ];
then
  nice "$SSSCRAPEPATH/bin/ssscrape-$SSSCRAPEWHAT" 2>"$SSSCRAPEPATH/log/ssscrape-$SSSCRAPEWHAT.err" &
  echo $! >$SSSCRAPEPIDFILE
  echo "Started Ssscrape on $HOST"
fi
