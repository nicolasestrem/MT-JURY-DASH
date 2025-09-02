#!/bin/bash

# Minify JavaScript files
echo "Minifying JavaScript files..."
for file in assets/js/*.js; do
    if [[ ! "$file" =~ \.min\.js$ ]]; then
        base="${file%.js}"
        echo "  Minifying: $file -> ${base}.min.js"
        # Use drop_console=true to remove all console.log statements in production
        uglifyjs "$file" -c drop_console=true -m -o "${base}.min.js" 2>/dev/null || echo "    Warning: Could not minify $file"
    fi
done

# Explicitly minify Plugin directory JavaScript files
echo "Minifying Plugin JavaScript files..."
for file in Plugin/assets/js/*.js; do
    if [[ ! "$file" =~ \.min\.js$ ]]; then
        base="${file%.js}"
        filename=$(basename "$file")
        echo "  Minifying: $file -> Plugin/assets/js/min/${filename%.js}.min.js"
        mkdir -p Plugin/assets/js/min
        # Use drop_console=true to remove all console.log statements in production
        uglifyjs "$file" -c drop_console=true -m -o "Plugin/assets/js/min/${filename%.js}.min.js" 2>/dev/null || echo "    Warning: Could not minify $file"
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
plugin_js_count=$(find Plugin/assets/js/min -name "*.min.js" 2>/dev/null | wc -l)
css_count=$(find assets/css -name "*.min.css" | wc -l)
total_js=$((js_count + plugin_js_count))

echo ""
echo "Summary:"
echo "  Minified JS files (assets): $js_count"
echo "  Minified JS files (Plugin): $plugin_js_count"
echo "  Total minified JS files: $total_js"
echo "  Minified CSS files: $css_count"