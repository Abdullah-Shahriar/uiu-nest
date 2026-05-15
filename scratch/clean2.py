import sys

path = 'c:/xampp/htdocs/GitHub/uiu-nest/pages/applications.php'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

replacements = {
    '🏢': '🏢 ',
    '·': '·',
    '৳': '৳',
    '🏠': '🏠',
    '📋': '📋',
    '📤': '📤',
    '📥': '📥',
    '✅': '✅',
    '❌': '❌',
    '🗑️': '🗑️',
    '🪪': '🪪',
    '👥': '👥'
}

for k, v in replacements.items():
    content = content.replace(k, '') # Wait, the user wants me to REMOVE the "foreign language"

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)
