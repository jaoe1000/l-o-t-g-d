#!/bin/bash
# fix_modules.sh - Dynamic Core File Extraction

# Ensure the target directory exists
mkdir -p /var/www/html/modules/

for dir in /tmp/modules/*; do
    if [ -d "$dir" ]; then
        base=$(basename "$dir")

        # Dynamic Check: Does this module have a subfolder with the exact same name?
        # (e.g., /tmp/modules/dwellings/dwellings/)
        if [ -d "$dir/$base" ]; then
            
            # 1. Move ALL .php files strictly from the FIRST level to the modules root
            # (This safely grabs dwellings.php, dwcastles.php, etc.)
            find "$dir" -maxdepth 1 -type f -name "*.php" -exec mv {} /var/www/html/modules/ \;

            # 2. Rescue the internal support files by moving them out of the redundant inner subfolder
            # (This moves func.php, lib.php, dohook/, etc. up one level into $dir)
            mv "$dir/$base"/* "$dir/" 2>/dev/null
            
            # 3. Remove the now-empty inner subfolder
            rmdir "$dir/$base" 2>/dev/null
        fi

        # Move the finalized, cleanly structured folder to the web root
        mv "$dir" /var/www/html/modules/
        
    # Catch any orphaned flat files sitting directly at the root of the clone
    elif [ -f "$dir" ] && [[ "$dir" == *.php ]]; then
        mv "$dir" /var/www/html/modules/
    fi
done
