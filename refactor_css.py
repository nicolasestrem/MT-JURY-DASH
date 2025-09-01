import os

def refactor_css_file(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    content = content.replace('body .', '#mt-plugin-wrapper .')
    content = content.replace(' !important', '')

    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)

if __name__ == '__main__':
    # Use a raw string for the path to avoid issues with backslashes
    css_dir = r'C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\assets\css\frontend'
    for filename in os.listdir(css_dir):
        if filename.endswith('.css'):
            file_path = os.path.join(css_dir, filename)
            refactor_css_file(file_path)
            print(f'Refactored {filename}')