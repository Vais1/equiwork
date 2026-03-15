import re

FILES = ['includes/flash.php', 'includes/header.php', 'includes/header2.php']

def process(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Flash colors
    content = content.replace('bg-blue-100 border-blue-400 text-blue-700 dark:bg-blue-900/30 dark:border-blue-700 dark:text-blue-300', 'bg-accent/10 border-accent/20 text-accent')

    # Headers
    content = content.replace('bg-white', 'bg-surface')
    content = content.replace('text-blue-700', 'text-accent')
    content = content.replace('ring-blue-400', 'ring-accent')
    content = content.replace('text-gray-900', 'text-text')
    content = content.replace('text-gray-700', 'text-text')
    content = content.replace('text-gray-600', 'text-muted')
    content = content.replace('text-gray-500', 'text-muted')
    content = content.replace('bg-gray-100', 'bg-surface')
    content = content.replace('bg-gray-300', 'bg-border')
    content = content.replace('ring-gray-300', 'ring-border')

    content = re.sub(
        r'class="bg-blue-700[^"]+"',
        r'class="bg-accent text-white px-5 py-2.5 rounded-lg font-semibold hover:bg-accent-hover active:scale-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-accent/50 whitespace-nowrap leading-none flex items-center"',
        content
    )

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

for f in FILES:
    process(f)

