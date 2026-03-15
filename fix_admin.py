import re

with open('admin/dashboard.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace hardcoded colors to CSS vars equivalents
content = content.replace('text-gray-900', 'text-text')
content = content.replace('text-gray-800', 'text-text')
content = content.replace('text-gray-700', 'text-text')
content = content.replace('text-gray-600', 'text-muted')
content = content.replace('text-gray-500', 'text-muted')

content = content.replace('bg-white', 'bg-surface')
content = content.replace('bg-gray-50', 'bg-bg')
content = content.replace('bg-gray-100', 'bg-bg')

content = content.replace('border-gray-200', 'border-border')
content = content.replace('border-gray-300', 'border-border')
content = content.replace('divide-gray-200', 'divide-border')

content = content.replace('bg-blue-100', 'bg-accent/10')
content = content.replace('text-blue-800', 'text-accent')
content = content.replace('text-blue-600', 'text-accent')
content = content.replace('ring-blue-500', 'ring-accent')
content = content.replace('ring-blue-300', 'ring-accent/50')

# Forms & Cards specifically
content = re.sub(r'class="[^"]*w-full px-4 py-2 border border-border rounded-md bg-surface text-text focus:outline-none focus:ring-2 focus:ring-accent[^"]*"', 'class="w-full border border-border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-accent focus:border-transparent bg-surface text-text transition-colors duration-150"', content)

# Buttons
content = re.sub(
    r'<button type="button" id="closeModalBtn" class="[^"]+">',
    r'<button type="button" id="closeModalBtn" class="border border-accent text-accent hover:bg-accent/10 px-5 py-2.5 rounded-lg font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-accent/50 active:scale-95">',
    content
)

content = re.sub(
    r'<button type="submit" class="[^"]+">',
    r'<button type="submit" class="bg-accent text-white px-5 py-2.5 rounded-lg font-semibold hover:bg-accent-hover active:scale-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-accent/50">',
    content
)

# dark mode removal
content = re.sub(r'\bdark:([a-z\-]+:)*[a-z0-9\-\/]+\b', '', content)

with open('admin/dashboard.php', 'w', encoding='utf-8') as f:
    f.write(content)
