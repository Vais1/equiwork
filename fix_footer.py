import re

with open('includes/footer.php', 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace('bg-white border-t border-gray-200/80', 'bg-surface border-t border-border')
content = content.replace('<div class="7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20 md:py-12">', '<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">')
content = content.replace('text-gray-500', 'text-muted')
content = content.replace('text-gray-300', 'text-muted/50')
content = content.replace('hover:text-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-400', 'hover:text-accent focus:outline-none focus:ring-2 focus:ring-accent/50')

with open('includes/footer.php', 'w', encoding='utf-8') as f:
    f.write(content)
