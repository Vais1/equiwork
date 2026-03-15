import re

with open('jobs.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Fix wrapper padding
content = content.replace('py-8"', 'py-12 md:py-20"')

# Fix double font-heading
content = content.replace('font-heading font-heading', 'font-heading')

# Fix job cards
content = re.sub(
    r'class="bg-white p-6 rounded-xl shadow-sm border border-gray-200\s+([^"]+)"',
    r'class="bg-surface border border-border rounded-xl shadow-sm p-6 hover:-translate-y-1 hover:shadow-md transition-all duration-200 \1"',
    content
)

# Apply badges correctly
# Look for <span class="... text-xs font-medium bg-gray-100 text-gray-800 ...">
# Badges tokens: inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-bg text-text border border-border
content = re.sub(
    r'class="[^"]*text-xs font-medium bg-gray-100 text-text px-2\.5 py-0\.5 rounded-full[^"]*"',
    r'class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-bg text-text border border-border"',
    content
)
content = re.sub(
    r'class="[^"]*text-xs font-medium bg-blue-100 text-blue-800 px-2\.5 py-0\.5 rounded-full[^"]*"',
    r'class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-accent/10 text-accent border border-accent/20"',
    content
)

# Fix sidebar
content = content.replace('bg-white p-5 rounded-xl shadow-sm border border-gray-200', 'bg-surface border border-border rounded-xl shadow-sm p-6')

# Check spacing rules, gap-4 md:gap-6 instead of manual margins. We will let it be for now since they are structural, but remove obvious bad ones.

with open('jobs.php', 'w', encoding='utf-8') as f:
    f.write(content)

