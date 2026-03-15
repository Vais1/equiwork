import os
import re
import glob

# Fix JS
js_path = 'assets/js/custom-controls.js'
if os.path.exists(js_path):
    with open(js_path, 'r') as f:
        content = f.read()
    
    # Select options
    content = content.replace("'bg-blue-100', 'dark:bg-blue-900', 'text-blue-900', 'dark:text-blue-100'", "'bg-accent/10', 'text-accent'")
    
    # Checkboxes
    content = content.replace("'bg-blue-600', 'border-blue-600'", "'bg-accent', 'border-accent'")
    content = content.replace("'bg-gray-100', 'dark:bg-gray-700', 'border-gray-300', 'dark:border-gray-600'", "'bg-surface', 'border-border'")
    
    with open(js_path, 'w') as f:
        f.write(content)

# Fix PHP files
files_to_fix = ['admin/login.php', 'apply_job.php', 'jobs.php', 'post_job.php', 'register.php', 'login.php', 'profile_update.php']

for file in files_to_fix:
    if not os.path.exists(file): continue
    with open(file, 'r') as f:
        content = f.read()

    # Generic replace
    content = content.replace('border-gray-100', 'border-border')
    content = content.replace('border-gray-200', 'border-border')
    content = content.replace('border-gray-300', 'border-border')
    content = content.replace('border-gray-700', 'border-border')
    
    content = content.replace('bg-gray-50', 'bg-bg')
    content = content.replace('bg-gray-100', 'bg-surface')
    content = content.replace('bg-gray-800', 'bg-surface')
    content = content.replace('bg-gray-900', 'bg-surface')
    
    content = content.replace('text-gray-300', 'text-muted')
    content = content.replace('text-gray-400', 'text-muted')
    content = content.replace('text-gray-500', 'text-muted')
    content = content.replace('text-gray-600', 'text-muted')
    content = content.replace('text-gray-700', 'text-text')
    content = content.replace('text-gray-800', 'text-text')
    content = content.replace('text-gray-900', 'text-text')
    
    content = content.replace('bg-blue-50', 'bg-accent/10')
    content = content.replace('bg-blue-100', 'bg-accent/10')
    content = content.replace('text-blue-600', 'text-accent')
    content = content.replace('text-blue-800', 'text-accent')
    
    content = content.replace('focus:ring-blue-300', 'focus:ring-accent/50')
    content = content.replace('focus:ring-blue-400', 'focus:ring-accent/50')
    content = content.replace('focus:ring-blue-500', 'focus:ring-accent/50')

    # Job specifically line 238
    content = re.sub(
        r'class="[^"]*bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 text-white[^"]*"',
        r'class="bg-accent text-white px-5 py-2.5 rounded-lg font-semibold hover:bg-accent-hover active:scale-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-accent/50 text-center w-full md:w-auto block"',
        content
    )

    with open(file, 'w') as f:
        f.write(content)

