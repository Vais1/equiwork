import re

with open('index.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Fix wrapper
content = content.replace('text-center px-4 sm:px-6 lg:px-8">', 'text-center">')

# Fix badge
content = content.replace('bg-accent/10/50 text-accent text-sm font-semibold mb-10 ring-1 ring-blue-600/10 shadow-sm backdrop-blur-sm', 
    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-bg text-text border border-border mb-10')
content = content.replace('gap-2.5 px-4 py-2 rounded-full inline-flex', 'gap-2.5 inline-flex')

# Fix A tags acting as buttons
content = re.sub(
    r'<a href="<\?php echo BASE_URL; \?>register\.php\?role=Seeker"[^>]+>',
    r'<a href="<?php echo BASE_URL; ?>register.php?role=Seeker" class="w-full sm:w-auto bg-accent text-white px-5 py-2.5 rounded-lg font-semibold hover:bg-accent-hover active:scale-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-accent/50 text-center">',
    content
)

content = re.sub(
    r'<a href="<\?php echo BASE_URL; \?>register\.php\?role=Employer"[^>]+>',
    r'<a href="<?php echo BASE_URL; ?>register.php?role=Employer" class="w-full sm:w-auto border border-accent text-accent hover:bg-accent/10 px-5 py-2.5 rounded-lg font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-accent/50 active:scale-95 text-center">',
    content
)

with open('index.php', 'w', encoding='utf-8') as f:
    f.write(content)

