#!/bin/bash
# Flatten nested modules: if /modules/dwellings/dwellings exists, move contents up
for dir in /tmp/modules/*; do
    if [ -d "$dir" ]; then
        base=$(basename "$dir")
        if [ -d "$dir/$base" ]; then
            mv "$dir/$base"/* "$dir/"
            rmdir "$dir/$base"
        fi
        mv "$dir" /var/www/html/modules/
    fi
done
