import re
import os

FILES = ['register.php', 'apply_job.php', 'post_job.php', 'profile_update.php', 'jobs.php']

CARD_STYLES = "bg-surface border border-border rounded-xl shadow-sm p-6 hover:-translate-y-1 hover:shadow-md transition-all duration-200"

def process(filepath):
    if not os.path.exists(filepath): return
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Cards
    def repl_card(match):
        original = match.group(0)
        mw = ''
        if 'max-w-' in original:
            mw = re.search(r'max-w-[a-z0-9\-]+', original).group(0)
        mx = 'mx-auto' if 'mx-auto' in original else ''
        mt = 'mt-12' if 'mt-12' in original else ('mt-8' if 'mt-8' in original else '')
        mb = 'mb-12' if 'mb-12' in original else ''
        
        # Combine parts
        parts = [p for p in [mw, mx, mt, mb, CARD_STYLES] if p]
        return re.sub(r'class="[^"]+"', f'class="{" ".join(parts)}"', original)
        
    content = re.sub(r'<div\s+class="[^"]*bg-white\s+rounded-[a-zA-Z0-9]+\s+shadow-(sm|md)\s+border\s+border-gray-200[^"]*"', repl_card, content)

    # Lists
    content = content.replace('bg-white border border-gray-300', 'bg-surface border border-border')
    
    # Secondary Buttons with white
    content = content.replace('border border-gray-300 text-text bg-white hover:bg-bg', 'border border-accent text-accent hover:bg-accent/10 focus:ring-2 focus:ring-accent/50 focus:outline-none')

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

for f in FILES:
    process(f)

