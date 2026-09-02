#!/bin/bash
set -e

REPO="/home/aldebara/git/drq-site"
TARGET="/home/aldebara/darwinriverquarries.com.au"
LOG="/home/aldebara/drq-deploy.log"

exec 9>"/home/aldebara/.drq-deploy.lock"
/usr/bin/flock -n 9 || exit 0

cd "$REPO"
/usr/bin/git fetch --quiet origin main

CURRENT=$(/usr/bin/git rev-parse HEAD)
REMOTE=$(/usr/bin/git rev-parse origin/main)

if [ "$CURRENT" = "$REMOTE" ]; then
  exit 0
fi

/usr/bin/git merge --ff-only origin/main
/bin/cp -a "$REPO/site/." "$TARGET/"
echo "$(date '+%Y-%m-%d %H:%M:%S') deployed $REMOTE" >> "$LOG"
