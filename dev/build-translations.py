"""Merge the singular ListNest catalog and build MO with Python's standard library."""
import ast
import gettext
import json
import struct
from pathlib import Path

root = Path(__file__).resolve().parents[1] / 'listnest' / 'languages'

def read_catalog(path):
    result, entry, field = {}, {}, None
    for line in (path.read_text(encoding='utf-8') + '\n\n').splitlines():
        if line.startswith(('msgctxt', 'msgid_plural', 'msgstr[')):
            raise ValueError('Plural/context catalog requires a full gettext merge tool')
        if not line.strip():
            if 'msgid' in entry:
                result[entry['msgid']] = entry.get('msgstr', '')
            entry, field = {}, None
        elif line.startswith(('msgid ', 'msgstr ')):
            field, value = line.split(' ', 1)
            entry[field] = ast.literal_eval(value)
        elif line.startswith('"') and field:
            entry[field] += ast.literal_eval(line)
    return result

translated = read_catalog(root / 'listnest-pl_PL.po')
translated.update({
    'Some products are no longer available. Remove them from your list before sending.': 'Niektóre produkty są już niedostępne. Usuń je z listy przed wysłaniem zapytania.',
    'Product no longer available': 'Produkt jest już niedostępny',
    'Thank you! Your inquiry has been received. We will get back to you as soon as possible.': 'Dziękujemy! Otrzymaliśmy Twoje zapytanie. Odpowiemy tak szybko, jak to możliwe.',
    'This inquiry is already being processed. Please wait a minute before sending again.': 'To zapytanie jest już przetwarzane. Poczekaj minutę przed ponownym wysłaniem.',
    'Setup and privacy': 'Konfiguracja i prywatność',
    'ListNest - setup and privacy': 'ListNest - konfiguracja i prywatność',
    'Create and publish a list under Lists → Add New List.': 'Utwórz i opublikuj listę w menu Listy → Dodaj nową listę.',
    'Enable the product button and select the built-in inquiry form in the list options.': 'W opcjach listy włącz przycisk produktu i wybierz wbudowany formularz zapytania.',
    'Create a page with the shortcode below. Select that page in the button or popup settings.': 'Utwórz stronę z poniższym shortcode. Wybierz tę stronę w ustawieniach przycisku lub okna potwierdzenia.',
    'Exclude list and form pages from full-page cache. Send a test inquiry and check Lists → Inquiries.': 'Wyklucz strony list i formularzy z pamięci podręcznej całych stron. Wyślij zapytanie testowe i sprawdź Listy → Zapytania.',
    'Lists use the WooCommerce session and expire with it. They are not shared between devices.': 'Listy korzystają z sesji WooCommerce i wygasają wraz z nią. Nie są synchronizowane między urządzeniami.',
    'Shortcode': 'Shortcode',
    'Stored inquiries': 'Zapisane zapytania',
    'Automatically delete inquiries older than this many days (0 = keep until manually deleted):': 'Automatycznie usuwaj zapytania starsze niż podana liczba dni (0 = przechowuj do ręcznego usunięcia):',
    'Permanently delete all lists and inquiries when this plugin is uninstalled.': 'Trwale usuń wszystkie listy i zapytania podczas odinstalowania wtyczki.',
    'Data is kept on uninstall unless you enable deletion here. Automatic cleanup runs daily through WP-Cron, in batches of 100 inquiries.': 'Dane pozostaną po odinstalowaniu, jeśli nie włączysz tutaj ich usuwania. Automatyczne czyszczenie działa codziennie przez WP-Cron, w partiach po 100 zapytań.',
    'You are not allowed to change these settings.': 'Nie masz uprawnień do zmiany tych ustawień.',
})
messages = read_catalog(root / 'listnest.pot')
header = translated[''].replace('ListNest 0.1.0', 'ListNest 1.0.0').replace('2026-10-01 12:00+0200', '2026-10-22 18:00+0200')
messages = {key: header if not key else translated.get(key, '') for key in messages}
missing = [key for key, value in messages.items() if not value]
if missing:
    raise ValueError(f'Missing Polish translations: {missing}')
quote = lambda value: json.dumps(value, ensure_ascii=False)
(root / 'listnest-pl_PL.po').write_text('\n\n'.join(f'msgid {quote(key)}\nmsgstr {quote(value)}' for key, value in messages.items()) + '\n', encoding='utf-8')
keys = sorted(messages)
originals = [key.encode() for key in keys]
values = [messages[key].encode() for key in keys]
count = len(keys)
start = 28 + 16 * count
orig_data = b'\0'.join(originals) + b'\0'
value_data = b'\0'.join(values) + b'\0'
def table(strings, offset):
    result = b''
    for value in strings:
        result += struct.pack('<2I', len(value), offset)
        offset += len(value) + 1
    return result
blob = struct.pack('<7I', 0x950412DE, 0, count, 28, 28 + count * 8, 0, 0)
blob += table(originals, start) + table(values, start + len(orig_data)) + orig_data + value_data
target = root / 'listnest-pl_PL.mo'
target.write_bytes(blob)
with target.open('rb') as source:
    catalog = gettext.GNUTranslations(source)
    assert catalog.gettext('Setup and privacy') == 'Konfiguracja i prywatność'
print(f'PASS: {count - 1} Polish translations compiled and verified')
