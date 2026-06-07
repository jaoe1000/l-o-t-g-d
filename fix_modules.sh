#!/bin/bash
# fix_modules.sh - Targeted at NB-Core's dual-structure

for dir in /tmp/modules/*; do
    if [ -d "$dir" ]; then
        base=$(basename "$dir")
        
        # Check if this is one of the "double-nested" NB-Core modules
        # It looks for a folder inside that matches the parent name
        if [ -d "$dir/$base" ]; then
            # Move everything from the inner folder up to the module root
            mv "$dir/$base"/* "$dir/"
            # Remove the now-empty inner folder
            rmdir "$dir/$base"
        fi
        
        # Move the entire resulting module folder to the web root
        mv "$dir" /var/www/html/modules/
    fi
done
