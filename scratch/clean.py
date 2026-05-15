import sys

path = 'c:/xampp/htdocs/GitHub/uiu-nest/pages/applications.php'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

replacements = {
    'ðŸ ¢ ': '🏢 ',
    'Â·': '·',
    'à§³': '৳',
    'ðŸ  ': '🏠',
    'ðŸ“‹': '📋',
    'ðŸ“¤': '📤',
    'ðŸ“¥': '📥',
    'âœ…': '✅',
    'â Œ': '❌',
    'ðŸ—‘ï¸ ': '🗑️',
    'ðŸªª': '🪪',
    'ðŸ‘¥': '👥',
    'ðŸ—‘ï¸  ': '🗑️ ',
    'ðŸ ¢': '🏢',
    'Â': ''
}

for k, v in replacements.items():
    content = content.replace(k, v)

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)
