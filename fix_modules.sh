#!/bin/bash
# fix_modules.sh - Two-Pass Category Flattening & Module Extraction

mkdir -p /var/www/html/modules/

# ==========================================
# PASS 1: Flatten Top-Level Categories
# ==========================================
# Any folder that DOES NOT meet the specific nested rule (module_name/module_name/) 
# is treated as a category wrapper. We dissolve it and move its contents up.
for dir in /tmp/modules/*; do
    if [ -d "$dir" ]; then
        base=$(basename "$dir")
        
        # If it lacks the double-nested inner folder, flatten it.
        if [ ! -d "$dir/$base" ]; then
            mv "$dir"/* /tmp/modules/ 2>/dev/null
            rm -rf "$dir"
        fi
    fi
done

# ==========================================
# PASS 2: Process Modules & Support Folders
# ==========================================
# /tmp/modules/ now only contains actual module wrappers or flat files.
for dir in /tmp/modules/*; do
    if [ -d "$dir" ]; then
        base=$(basename "$dir")

        # If it meets the double-nested rule (e.g., dwellings/dwellings/)
        if [ -d "$dir/$base" ]; then
            
            # 1. Promote ALL core .php files from this wrapper to the modules root
            find "$dir" -maxdepth 1 -type f -name "*.php" -exec mv {} /var/www/html/modules/ \;

            # 2. Rescue any sibling support folders (e.g., extracting dwellings_pvp/)
            for sub in "$dir"/*; do
                if [ -d "$sub" ]; then
                    sub_base=$(basename "$sub")
                    # If it is NOT the primary inner nested folder, move it to the web root
                    if [ "$sub_base" != "$base" ]; then
                        mv "$sub" /var/www/html/modules/
                    fi
                fi
            done

            # 3. Collapse the primary inner nested folder
            # (Moving run/, images/, etc., up to replace the wrapper)
            mv "$dir/$base"/* "$dir/" 2>/dev/null
            rmdir "$dir/$base" 2>/dev/null
        fi

        # Move the cleanly formatted support folder to the web root
        mv "$dir" /var/www/html/modules/
        
    elif [ -f "$dir" ] && [[ "$dir" == *.php ]]; then
        # Catch orphaned flat files and push them to the web root
        mv "$dir" /var/www/html/modules/
    fi
done
