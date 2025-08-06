#!/bin/bash

echo "🔍 Finding and creating missing controllers..."

# Find all unique controller class names from 'use' statements in route files
CONTROLLERS=$(grep -rhE "^use App\\Http\\Controllers\\[a-zA-Z0-9_]+Controller;" routes/ | sed -E 's/use (.*);/\1/' | sort -u)

if [ -z "$CONTROLLERS" ]; then
    echo "No controllers found in 'use' statements. Trying a fallback search..."
    CONTROLLERS=$(grep -rhE "App\\Http\\Controllers\\[a-zA-Z0-9_]+Controller" routes/ | sed -E "s/.*(App\\Http\\Controllers\\[a-zA-Z0-9_]+Controller).*/\1/" | sort -u)
    if [ -z "$CONTROLLERS" ]; then
        echo "Fallback failed. No controllers found. Exiting."
        exit 0
    fi
fi

echo "
Found the following controllers to check:"
echo "$CONTROLLERS"
echo ""

for CONTROLLER_CLASS in $CONTROLLERS; do
    # Convert namespace to file path
    FILE_PATH=$(echo "$CONTROLLER_CLASS" | sed 's|\\|/|g' | sed 's|App|app|').".php"

    if [ -f "$FILE_PATH" ]; then
        echo "✅ Controller exists: $FILE_PATH"
    else
        echo "❌ Missing controller: $FILE_PATH. Creating..."
        
        DIR_PATH=$(dirname "$FILE_PATH")
        CLASS_NAME=$(basename "$FILE_PATH" .php)
        NAMESPACE=$(echo "$CONTROLLER_CLASS" | sed 's|\\\\[^\\\\]*$||')

        mkdir -p "$DIR_PATH"

        # Create the controller file with boilerplate content
        cat > "$FILE_PATH" << EOL
<?php

namespace ${NAMESPACE};

use Illuminate\\Routing\\Controller as BaseController;
use Illuminate\\Http\\Request;

class ${CLASS_NAME} extends BaseController
{
    // This is a placeholder controller created by a script.
}
EOL
        echo "✅ Created ${FILE_PATH}"
    fi
done

echo "
Running composer dump-autoload to register new classes..."
composer dump-autoload

echo "
Controller creation process complete."