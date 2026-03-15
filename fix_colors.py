import re

FILES = ['includes/header.php', 'includes/header2.php']

def hex_to_rgb(hex_str):
    hex_str = hex_str.lstrip('#')
    return f"{int(hex_str[0:2], 16)} {int(hex_str[2:4], 16)} {int(hex_str[4:6], 16)}"

for filepath in FILES:
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Find the CSS variables block
    # Replace the hex codes with rgb values
    def repl_var(m):
        var_name = m.group(1)
        hex_val = m.group(2)
        return f"{var_name}: {hex_to_rgb(hex_val)};"

    content = re.sub(r'(--color-[a-zA-Z\-]+):\s*(#[0-9a-fA-F]{6});', repl_var, content)

    # Now update the Tailwind config mapping to use rgb with alpha
    def repl_tw(m):
        tw_key = m.group(1)
        var_name = m.group(2)
        return f"{tw_key}: 'rgb(var({var_name}) / <alpha-value>)'"

    content = re.sub(r"([a-zA-Z\-]+):\s*'var\((--color-[a-zA-Z\-]+)\)'", repl_tw, content)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

