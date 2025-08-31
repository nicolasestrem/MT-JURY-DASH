#!/bin/bash

# Minify JavaScript files
echo "Minifying JavaScript files..."
for file in assets/js/*.js; do
    if [[ ! "$file" =~ \.min\.js$ ]]; then
        base="${file%.js}"
        echo "  Minifying: $(basename $file)"
        npx uglify-js "$file" -c -m -o "${base}.min.js" 2>&1 | grep -v "npx: installed"
    fi
done

# Minify CSS files
echo ""
echo "Minifying CSS files..."

# Process main CSS directory
for file in assets/css/*.css; do
    if [[ ! "$file" =~ \.min\.css$ ]]; then
        base="${file%.css}"
        echo "  Minifying: $(basename $file)"
        npx clean-css-cli -o "${base}.min.css" "$file" 2>&1 | grep -v "npx: installed"
    fi
done

# Process v4 CSS directory
echo "  Processing v4 directory..."
for file in assets/css/v4/*.css; do
    if [[ ! "$file" =~ \.min\.css$ ]]; then
        base="${file%.css}"
        echo "  Minifying: v4/$(basename $file)"
        npx clean-css-cli -o "${base}.min.css" "$file" 2>&1 | grep -v "npx: installed"
    fi
done

# Process blocks CSS directory
if [ -d "assets/css/blocks" ]; then
    echo "  Processing blocks directory..."
    for file in assets/css/blocks/*.css; do
        if [[ -f "$file" && ! "$file" =~ \.min\.css$ ]]; then
            base="${file%.css}"
            echo "  Minifying: blocks/$(basename $file)"
            npx clean-css-cli -o "${base}.min.css" "$file" 2>&1 | grep -v "npx: installed"
        fi
    done
fi

echo ""
echo "Minification complete!"

# Count minified files
js_count=$(find assets/js -name "*.min.js" 2>/dev/null | wc -l)
css_count=$(find assets/css -name "*.min.css" 2>/dev/null | wc -l)

echo ""
echo "Summary:"
echo "  Minified JS files: $js_count"
echo "  Minified CSS files: $css_count"
echo ""
echo "File sizes comparison:"
echo "  JavaScript:"
for file in assets/js/*.js; do
    if [[ ! "$file" =~ \.min\.js$ ]] && [[ -f "${file%.js}.min.js" ]]; then
        orig_size=$(wc -c < "$file")
        min_size=$(wc -c < "${file%.js}.min.js")
        reduction=$((100 - (min_size * 100 / orig_size)))
        printf "    %-40s %7d -> %7d bytes (%d%% reduction)\n" "$(basename $file)" $orig_size $min_size $reduction
    fi
done