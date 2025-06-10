# Elementor Inline Translate

WordPress plugin til direkte oversættelse af Elementor-elementer i editoren med DeepL API.

## Funktioner

- **Direkte oversættelse** i Elementor editor
- **Bulk oversættelse** af hele sider  
- **HTML formatering bevares** for text-editor widgets
- **PolyLang integration** for kopi fra hovedsprog
- **DeepL API** for præcise oversættelser

## Installation

1. Upload plugin-filerne til `/wp-content/plugins/elementor-inline-translate/`
2. Aktiver pluginet i WordPress admin
3. Konfigurer din DeepL API-nøgle i `elementor-inline-translate.php` (linje ~175)

## Brug

### Individuel oversættelse
1. Åbn en Elementor side til redigering
2. Klik på "Oversæt" knappen ved elementer
3. Vælg målsprog og oversæt

### Bulk oversættelse
1. Klik "Bulk Oversættelse" i Elementor navigator eller top-bar
2. Vælg målsprog (DE, EN, FR, ES, etc.)
3. Vent på batch processing af alle elementer

## Understøttede Widgets

- Heading (overskrifter)
- Text Editor (tekst med HTML formatering)
- Button (knapper)
- Icon Box (ikon bokse med titel + beskrivelse) 
- Divider (skillelinjer med tekst)

## Krav

- WordPress 5.0+
- Elementor 3.5.0+
- PHP 7.4+
- DeepL API nøgle (gratis eller Pro)

## Version

1.2.0 - Performance optimering og forbedret bulk oversættelse