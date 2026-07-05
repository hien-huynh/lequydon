import json
from datetime import datetime, timezone
from html.parser import HTMLParser
from pathlib import Path


class MenuParser(HTMLParser):
    def __init__(self):
        super().__init__()
        self.menu = []
        self.list_stack = [self.menu]
        self.item_stack = []

    def handle_starttag(self, tag, attrs):
        if tag == 'li':
            item = {'title': '', 'url': '', 'children': []}
            self.list_stack[-1].append(item)
            self.item_stack.append(item)
        elif tag == 'ul' and self.item_stack:
            child_list = []
            self.item_stack[-1]['children'] = child_list
            self.list_stack.append(child_list)
        elif tag == 'a' and self.item_stack:
            self.item_stack[-1]['url'] = '#'

    def handle_endtag(self, tag):
        if tag == 'li' and self.item_stack:
            self.item_stack.pop()
        elif tag == 'ul' and len(self.list_stack) > 1:
            self.list_stack.pop()

    def handle_data(self, data):
        if self.item_stack and data.strip():
            current_title = self.item_stack[-1]['title']
            if current_title:
                current_title += ' '
            self.item_stack[-1]['title'] = current_title + data.strip()


def build_plugin_payload(tree):
    items = []
    next_id = 1
    menu_order = 1

    def walk(nodes, parent_id=0):
        nonlocal next_id, menu_order
        for node in nodes:
            item_id = next_id
            next_id += 1
            items.append({
                'id': item_id,
                'parent': parent_id,
                'menu_order': menu_order,
                'title': node['title'],
                'url': node.get('url', '#'),
                'target': '',
                'attr_title': '',
                'description': '',
                'xfn': '',
                'classes': [],
                'visibility': 'everyone',
                'reference': {
                    'type': 'custom',
                    'object': 'custom'
                }
            })
            menu_order += 1
            walk(node.get('children', []), item_id)

    walk(tree)

    return {
        'format': 'import-export-menu',
        'schema_version': '1.1',
        'generator': 'Import Export Menu',
        'plugin_version': '3.0.0',
        'exported_at': datetime.now(timezone.utc).replace(microsecond=0).isoformat(),
        'site_url': 'http://lequydon.local',
        'menus': [
            {
                'name': 'Home',
                'slug': 'home',
                'locations': ['primary'],
                'auto_add_pages': False,
                'items': items
            }
        ]
    }


def parse_menu(html_path: Path, output_path: Path) -> None:
    parser = MenuParser()
    parser.feed(html_path.read_text(encoding='utf-8'))
    payload = build_plugin_payload(parser.menu)
    output_path.write_text(
        json.dumps(payload, ensure_ascii=False, indent=4),
        encoding='utf-8'
    )


if __name__ == '__main__':
    base_dir = Path(__file__).resolve().parent
    parse_menu(base_dir / 'menu.html', base_dir / 'menu_data.json')
    print('Đã chuyển menu thành công vào file: menu_data.json')