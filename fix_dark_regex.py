import os
import re

FILES = [
    'index.php', 'login.php', 'register.php', 'post_job.php',
    'apply_job.php', 'profile_update.php', 'jobs.php',
    'includes/footer.php', 'includes/header.php', 'includes/header2.php',
    'admin/dashboard.php'
]

def process_file(filepath):
    if not os.path.exists(filepath):
        return

    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Match anything like dark:hover:bg-blue-500
    # Also fix the weird leftovers like ` :bg-blue-500` which came from matching just `dark` and leaving `:bg-blue-500`
    
    content = re.sub(r'\bdark:([a-z\-]+:)*[a-z0-9\-\/]+\b', '', content)
    # clean up the artifacts like ` :bg-` or `:hover:bg-`
    content = re.sub(r'\s+:[a-z\-]+(:[a-z\-]+)*-[a-z0-9\-\/]+', '', content)
    
    # Clean multiple spaces inside class attributes again
    content = re.sub(r'class="([^"]+)"', lambda m: f'class="{" ".join(m.group(1).split())}"', content)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

for f in FILES:
    process_file(f)

