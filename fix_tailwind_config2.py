import re

FILES = ['includes/header.php', 'includes/header2.php']

for filepath in FILES:
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Find the bounds of the script tag for tailwind.config
    start_tag = '<script>\n        tailwind.config'
    end_tag = '</script>\n\n    <!-- Prevents FOUC'
    
    if start_tag in content and end_tag in content:
        before = content.split(start_tag)[0]
        after = content.split(end_tag)[1]
        
        new_script = """<script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"DM Sans"', 'ui-sans-serif', 'sans-serif'],
                        heading: ['Outfit', 'ui-sans-serif', 'sans-serif'],
                    },
                    colors: {
                        bg: 'var(--color-bg)',
                        surface: 'var(--color-surface)',
                        border: 'var(--color-border)',
                        text: 'var(--color-text)',
                        muted: 'var(--color-muted)',
                        accent: 'var(--color-accent)',
                        'accent-hover': 'var(--color-accent-hover)',
                    }
                }
            }
        }
    </script>

    <!-- Prevents FOUC"""
        
        content = before + new_script + after
        
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
            
