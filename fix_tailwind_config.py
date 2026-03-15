import re

FILES = ['includes/header.php', 'includes/header2.php']

for filepath in FILES:
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # The broken part looks like this:
    #         }
    #                 }
    #             }
    #         }
    #     </script>
    
    # Let's replace the whole tailwind.config block cleanly
    broken_block_regex = r'tailwind\.config\s*=\s*\{.*?\}(\s*\}){3}'
    
    fixed_config = """tailwind.config = {
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
        }"""
    
    content = re.sub(broken_block_regex, fixed_config, content, flags=re.DOTALL)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

