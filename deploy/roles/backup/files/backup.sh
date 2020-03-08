#!/bin/sh

backup-manager

megals -n -l /Root/backups/popcorn-gc  | awk '{"date +%Y-%m-%d -d \"2 week ago\"" | getline old; if ( old > $5) print $7;}' | xargs -I'{}' megarm /Root/backups/popcorn-gc/{}

megacopy -l /var/archives/ -r /Root/backups/popcorn-gc --reload 2> /dev/null
