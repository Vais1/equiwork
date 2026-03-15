import os
import re

FILES = [
    'index.php', 'login.php', 'register.php', 'post_job.php',
    'apply_job.php', 'profile_update.php', 'jobs.php'
]

# Tokens
BTN_PRIMARY = "bg-accent text-white px-5 py-2.5 rounded-lg font-semibold hover:bg-accent-hover active:scale-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-accent/50"
BTN_SECONDARY = "border border-accent text-accent hover:bg-accent/10 px-5 py-2.5 rounded-lg font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-accent/50 active:scale-95"
INPUT_STYLES = "w-full border border-border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-accent focus:border-transparent bg-surface text-text transition-colors duration-150"
CARD_STYLES = "bg-surface border border-border rounded-xl shadow-sm p-6 hover:-translate-y-1 hover:shadow-md transition-all duration-200"

def apply_to_file(filepath):
    if not os.path.exists(filepath): return
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # 1. Dark mode wipe
    content = re.sub(r'\bdark:([a-z\-]+:)*[a-z0-9\-\/]+\b', '', content)
    
    # 2. Main layout standard wrapper (only top level if possible, or section)
    # Be careful not to replace inner max-w-md wrappers for forms with 7xl.
    # So we only replace max-w-5xl, max-w-6xl etc if it's the main container.
    content = content.replace('<section class="max-w-5xl mx-auto py-12 md:py-20', '<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20')

    # 3. Input fields
    # Look for <input ... class="..."> and replace class.
    # Better: find class attributes of <input, <select, <textarea
    
    def repl_input(match):
        # replace the class attribute value
        return re.sub(r'class="[^"]+"', f'class="{INPUT_STYLES}"', match.group(0))
        
    content = re.sub(r'<input\s+[^>]*type="(?:text|email|password|tel|url|number)"[^>]*>', repl_input, content)
    content = re.sub(r'<select\s+[^>]*>', repl_input, content)
    content = re.sub(r'<textarea\s+[^>]*>', repl_input, content)

    # 4. Buttons (Primary and Secondary logic)
    # If the button had "bg-white" and "text-gray-900" it's secondary, else primary.
    def repl_btn(match):
        btn_html = match.group(0)
        # Keep type, id, name intact
        # Just replace the class string
        if 'bg-white' in btn_html and 'text-gray' in btn_html:
            new_class = BTN_SECONDARY
        elif 'bg-blue' in btn_html or 'bg-gray-900' in btn_html or 'bg-indigo' in btn_html:
            new_class = BTN_PRIMARY
        else:
            # Fallback to primary
            new_class = BTN_PRIMARY
            
        # if the button has w-full or other specific structural things like sm:w-auto, maybe we should keep them?
        # The instructions say "Apply component token styles from Phase 1 to every instance. Unify all button variants"
        # We can append w-full if it was there.
        if 'w-full' in btn_html:
            new_class = 'w-full ' + new_class
        if 'sm:w-auto' in btn_html:
            new_class = 'sm:w-auto ' + new_class
            
        return re.sub(r'class="[^"]+"', f'class="{new_class}"', btn_html)

    content = re.sub(r'<button\s+[^>]*class="[^"]+"[^>]*>', repl_btn, content)
    
    # Do the same for <a> acting as buttons (hero CTAs)
    # If they look like a button (padding, bg-blue, rounded, etc)
    # We can just manually replace the known ones in index.php
    
    # 5. Fix card layouts
    # Look for "bg-white rounded-2xl shadow-sm border border-gray-200" or similar
    # Replace with CARD_STYLES, but keeping structure like max-w-md if it's a form wrapper.
    def repl_card(match):
        original = match.group(0)
        if 'max-w-' in original:
            # extract max-w class
            mw = re.search(r'max-w-[a-z0-9\-]+', original).group(0)
            mx = 'mx-auto' if 'mx-auto' in original else ''
            mt = 'mt-12' if 'mt-12' in original else ''
            return re.sub(r'class="[^"]+"', f'class="{mw} {mx} {mt} {CARD_STYLES}"', original)
        return re.sub(r'class="[^"]+"', f'class="{CARD_STYLES}"', original)

    content = re.sub(r'<div\s+class="[^"]*bg-white\s+rounded-[a-zA-Z0-9]+\s+shadow-sm\s+border\s+border-gray-200[^"]*"', repl_card, content)

    # General text color fixes
    content = content.replace('text-gray-900', 'text-text')
    content = content.replace('text-gray-800', 'text-text')
    content = content.replace('text-gray-700', 'text-text')
    content = content.replace('text-gray-600', 'text-muted')
    content = content.replace('text-gray-500', 'text-muted')
    content = content.replace('bg-gray-50', 'bg-bg')
    content = content.replace('bg-blue-50', 'bg-accent/10')
    content = content.replace('text-blue-600', 'text-accent')
    content = content.replace('text-blue-700', 'text-accent')

    # Typography
    content = re.sub(r'class="([^"]*text-[45]xl[^"]*)"', lambda m: f'class="{m.group(1)} font-heading"', content)
    content = re.sub(r'class="([^"]*text-3xl[^"]*)"', lambda m: f'class="{m.group(1)} font-heading"', content)
    content = re.sub(r'class="([^"]*text-2xl[^"]*)"', lambda m: f'class="{m.group(1)} font-heading"', content)

    # Clean multiple spaces inside class attributes again
    content = re.sub(r'class="([^"]+)"', lambda m: f'class="{" ".join(m.group(1).split())}"', content)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

for f in FILES:
    apply_to_file(f)

