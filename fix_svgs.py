import re
import os
import glob

files = glob.glob('**/*.php', recursive=True)

for filepath in files:
    if not os.path.exists(filepath): continue
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Add aria-hidden="true" to SVGs that don't have it
    def repl_svg(m):
        original = m.group(0)
        if 'aria-hidden' not in original and 'role="img"' not in original:
            return original.replace('<svg ', '<svg aria-hidden="true" ')
        return original
        
    content = re.sub(r'<svg\s+[^>]*>', repl_svg, content)

    # Images
    def repl_img(m):
        original = m.group(0)
        if 'loading=' not in original:
            return original.replace('<img ', '<img loading="lazy" ')
        return original

    content = re.sub(r'<img\s+[^>]*>', repl_img, content)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

