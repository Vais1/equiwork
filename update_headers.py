import os
import re

def update_file(filepath):
    if not os.path.exists(filepath):
        return

    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # 1. Update fonts
    content = re.sub(
        r'<link href="https://fonts\.googleapis\.com/css2\?family=Inter:wght[^"]+" rel="stylesheet">',
        r'<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">',
        content
    )

    # 2. Inject CSS variables
    style_block = """
    <style>
        :root {
            --color-bg: #f8fafc;
            --color-surface: #ffffff;
            --color-border: #e2e8f0;
            --color-text: #0f172a;
            --color-muted: #64748b;
            --color-accent: #2563eb;
            --color-accent-hover: #1d4ed8;
        }
        .dark {
            --color-bg: #0f172a;
            --color-surface: #1e293b;
            --color-border: #334155;
            --color-text: #f8fafc;
            --color-muted: #94a3b8;
            --color-accent: #3b82f6;
            --color-accent-hover: #60a5fa;
        }
    </style>
"""
    # Insert style block right before </head> if not already there
    if '--color-bg' not in content:
        content = content.replace('</head>', f'{style_block}</head>')

    # 3. Update Tailwind config
    tw_config = """tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"DM Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        heading: ['Outfit', 'ui-sans-serif', 'system-ui', 'sans-serif'],
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
    
    # regex replace the entire tailwind.config object
    content = re.sub(
        r'tailwind\.config\s*=\s*\{.*?\}\s*\}',
        tw_config,
        content,
        flags=re.DOTALL
    )

    # 4. Update body class
    content = re.sub(
        r'<body class="[^"]+">',
        r'<body class="bg-bg text-text transition-colors duration-200 min-h-screen flex flex-col font-sans tracking-tight">',
        content
    )

    # 5. Fix sticky nav classes
    content = re.sub(
        r'<header class="[^"]*sticky top-0 z-50[^"]*">',
        r'<header class="sticky top-0 z-50 w-full backdrop-blur-md bg-bg/80 border-b border-border shadow-sm transition-colors duration-300">',
        content
    )

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

update_file('includes/header.php')
update_file('includes/header2.php')

