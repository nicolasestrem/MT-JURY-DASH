#!/bin/bash

# Minify JavaScript files
echo "Minifying JavaScript files..."
for file in assets/js/*.js; do
    if [[ ! "$file" =~ \.min\.js$ ]]; then
        base="${file%.js}"
        echo "  Minifying: $file -> ${base}.min.js"
        uglifyjs "$file" -c -m -o "${base}.min.js" 2>/dev/null || echo "    Warning: Could not minify $file"
    fi
done

# Minify CSS files
echo "Minifying CSS files..."

# Process main CSS directory
for file in assets/css/*.css; do
    if [[ ! "$file" =~ \.min\.css$ ]]; then
        base="${file%.css}"
        echo "  Minifying: $file -> ${base}.min.css"
        cleancss -o "${base}.min.css" "$file" 2>/dev/null || echo "    Warning: Could not minify $file"
    fi
done

# Process v4 CSS directory
for file in assets/css/v4/*.css; do
    if [[ ! "$file" =~ \.min\.css$ ]]; then
        base="${file%.css}"
        echo "  Minifying: $file -> ${base}.min.css"
        cleancss -o "${base}.min.css" "$file" 2>/dev/null || echo "    Warning: Could not minify $file"
    fi
done

# Process blocks CSS directory
if [ -d "assets/css/blocks" ]; then
    for file in assets/css/blocks/*.css; do
        if [[ -f "$file" && ! "$file" =~ \.min\.css$ ]]; then
            base="${file%.css}"
            echo "  Minifying: $file -> ${base}.min.css"
            cleancss -o "${base}.min.css" "$file" 2>/dev/null || echo "    Warning: Could not minify $file"
        fi
    done
fi

echo "Minification complete!"

# Count minified files
js_count=$(find assets/js -name "*.min.js" | wc -l)
css_count=$(find assets/css -name "*.min.css" | wc -l)

echo ""
echo "Summary:"
echo "  Minified JS files: $js_count"
echo "  Minified CSS files: $css_count"