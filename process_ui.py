import os
import re

FILES = [
    'index.php',
    'login.php',
    'register.php',
    'post_job.php',
    'apply_job.php',
    'profile_update.php',
    'jobs.php',
    'includes/footer.php',
    'admin/dashboard.php' # just in case
]

# Patterns for component tokens
BUTTON_PRIMARY = 'bg-accent text-white px-5 py-2.5 rounded-lg font-semibold hover:bg-accent-hover active:scale-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-accent/50'
BUTTON_SECONDARY = 'border border-accent text-accent hover:bg-accent/10 px-5 py-2.5 rounded-lg font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-accent/50 active:scale-95'
INPUT_STYLES = 'w-full border border-border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-accent focus:border-transparent bg-surface text-text transition-colors duration-150'
CARD_STYLES = 'bg-surface border border-border rounded-xl shadow-sm p-6 hover:-translate-y-1 hover:shadow-md transition-all duration-200'
BADGE_STYLES = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-bg text-text border border-border'

def process_file(filepath):
    if not os.path.exists(filepath):
        return

    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # 1. Remove all 'dark:[a-zA-Z0-9\-\/]+' classes
    content = re.sub(r'\bdark:[a-zA-Z0-9\-\/]+\b', '', content)
    
    # 2. Cleanup multiple spaces in classes
    content = re.sub(r'class="([^"]+)"', lambda m: f'class="{" ".join(m.group(1).split())}"', content)

    # Replace layout wrappers
    content = re.sub(r'<section class="max-w-5xl[^"]*">', r'<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20">', content)
    content = re.sub(r'<div class="max-w-[a-zA-Z0-9]+ mx-auto[^"]*">', lambda m: m.group(0).replace(m.group(0)[12:m.group(0).find(' ', 12)], '7xl').replace('py-8', 'py-12 md:py-20'), content)
    
    # Let's write updated content back temporarily before doing complex replacements
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

for f in FILES:
    process_file(f)

