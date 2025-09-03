#!/bin/bash

# Minify assets script for Mobility Trailblazers
# Creates minified versions of CSS and JS files

echo "Starting asset minification..."

# Ensure we're in the right directory
if [ ! -d "Plugin" ]; then
    echo "Error: Plugin directory not found. Please run from project root."
    exit 1
fi

# We'll use npx to run the tools
echo "Using npx to run minification tools..."

# Create min directories if they don't exist
mkdir -p Plugin/assets/js/min
mkdir -p Plugin/assets/css/min

# Minify JavaScript files
echo "Minifying JavaScript files..."
for file in Plugin/assets/js/*.js; do
    if [ -f "$file" ]; then
        filename=$(basename "$file")
        # Skip already minified files
        if [[ ! "$filename" == *.min.js ]]; then
            echo "  - Minifying $filename"
            npx terser "$file" -c -m --source-map "url='$filename.map'" -o "Plugin/assets/js/min/${filename%.js}.min.js"
        fi
    fi
done

# Minify CSS files
echo "Minifying CSS files..."
for file in Plugin/assets/css/*.css; do
    if [ -f "$file" ]; then
        filename=$(basename "$file")
        # Skip already minified files
        if [[ ! "$filename" == *.min.css ]]; then
            echo "  - Minifying $filename"
            npx cleancss -o "Plugin/assets/css/min/${filename%.css}.min.css" "$file"
        fi
    fi
done

echo "Asset minification complete!"
echo "Minified files created in:"
echo "  - Plugin/assets/js/min/"
echo "  - Plugin/assets/css/min/"