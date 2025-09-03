#!/usr/bin/env python3
"""
Compile PO file to MO file for WordPress localization
"""
import os
import sys
import struct
import re
from pathlib import Path

def parse_po_file(po_path):
    """Parse a PO file and extract msgid/msgstr pairs"""
    translations = {}
    current_msgid = None
    current_msgstr = None
    is_multiline_msgid = False
    is_multiline_msgstr = False
    
    with open(po_path, 'r', encoding='utf-8') as f:
        lines = f.readlines()
    
    i = 0
    while i < len(lines):
        line = lines[i].strip()
        
        # Skip comments and empty lines
        if line.startswith('#') or not line:
            i += 1
            continue
        
        # Handle msgid
        if line.startswith('msgid '):
            if current_msgid is not None and current_msgstr is not None:
                # Save previous translation
                translations[current_msgid] = current_msgstr
            
            # Extract msgid
            match = re.match(r'msgid "(.*)"', line)
            if match:
                current_msgid = match.group(1)
                current_msgstr = None
                is_multiline_msgid = False
                is_multiline_msgstr = False
            else:
                current_msgid = ""
                is_multiline_msgid = True
        
        # Handle msgstr
        elif line.startswith('msgstr '):
            # Extract msgstr
            match = re.match(r'msgstr "(.*)"', line)
            if match:
                current_msgstr = match.group(1)
                is_multiline_msgstr = False
            else:
                current_msgstr = ""
                is_multiline_msgstr = True
        
        # Handle multiline strings
        elif line.startswith('"') and line.endswith('"'):
            content = line[1:-1]
            if is_multiline_msgid:
                current_msgid += content
            elif is_multiline_msgstr:
                current_msgstr += content
        
        i += 1
    
    # Save last translation
    if current_msgid is not None and current_msgstr is not None:
        translations[current_msgid] = current_msgstr
    
    # Remove empty msgid (header)
    if "" in translations:
        del translations[""]
    
    return translations

def generate_mo_file(translations, mo_path):
    """Generate MO file from translations dictionary"""
    
    # Sort translations by msgid
    sorted_translations = sorted(translations.items())
    
    # Prepare data
    ids = b''.join([msgid.encode('utf-8') + b'\x00' for msgid, _ in sorted_translations])
    strs = b''.join([msgstr.encode('utf-8') + b'\x00' for _, msgstr in sorted_translations])
    
    # Calculate offsets
    keystart = 7 * 4  # Size of header
    valuestart = keystart + len(sorted_translations) * 8
    
    # Generate offset tables
    koffsets = []
    voffsets = []
    
    ids_offset = valuestart + len(sorted_translations) * 8
    strs_offset = ids_offset + len(ids)
    
    current_id_offset = 0
    current_str_offset = 0
    
    for msgid, msgstr in sorted_translations:
        msgid_bytes = msgid.encode('utf-8')
        msgstr_bytes = msgstr.encode('utf-8')
        
        koffsets.append((len(msgid_bytes), ids_offset + current_id_offset))
        voffsets.append((len(msgstr_bytes), strs_offset + current_str_offset))
        
        current_id_offset += len(msgid_bytes) + 1
        current_str_offset += len(msgstr_bytes) + 1
    
    # Generate MO file header
    header = struct.pack('Iiiiiii',
        0x950412de,  # Magic number
        0,           # Version
        len(sorted_translations),  # Number of strings
        keystart,    # Offset of table with original strings
        valuestart,  # Offset of table with translation strings
        0,           # Size of hashing table
        0            # Offset of hashing table
    )
    
    # Generate offset tables
    offsets = b''
    for length, offset in koffsets:
        offsets += struct.pack('ii', length, offset)
    for length, offset in voffsets:
        offsets += struct.pack('ii', length, offset)
    
    # Write MO file
    with open(mo_path, 'wb') as f:
        f.write(header)
        f.write(offsets)
        f.write(ids)
        f.write(strs)
    
    return len(sorted_translations)

def main():
    # Define file paths
    po_file = Path(r"C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\languages\mobility-trailblazers-de_DE.po")
    mo_file = Path(r"C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\languages\mobility-trailblazers-de_DE.mo")
    
    # Check if PO file exists
    if not po_file.exists():
        print(f"Error: PO file not found at: {po_file}")
        sys.exit(1)
    
    print(f"Compiling PO file: {po_file}")
    
    # Parse PO file
    try:
        translations = parse_po_file(po_file)
        print(f"Parsed {len(translations)} translations")
        
        # Show some statistics
        translated = sum(1 for msgid, msgstr in translations.items() if msgstr)
        print(f"Translated strings: {translated}/{len(translations)}")
        
    except Exception as e:
        print(f"Error parsing PO file: {e}")
        sys.exit(1)
    
    # Generate MO file
    try:
        count = generate_mo_file(translations, mo_file)
        print(f"✓ MO file compiled successfully!")
        print(f"  Output: {mo_file}")
        print(f"  Strings: {count}")
        print(f"  Size: {mo_file.stat().st_size:,} bytes")
        
    except Exception as e:
        print(f"Error generating MO file: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()