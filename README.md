# Elementor Inline Oversættelse

Et WordPress plugin der gør det muligt at oversætte tek### Understøttede Widgets

- **Heading Widget** - Oversætter title feltet
- **Text Editor Widget** - Oversætter editor indholdet **med bevarelse af HTML formatering**
- **Button Widget** - Oversætter button teksten

### HTML Formatering

Text Editor widgets bevarer automatisk deres HTML formatering under oversættelse:

- **Styling bevares**: Fed tekst, kursiv, understreg osv.
- **Links bevares**: Alle links og deres attributter forbliver intakte
- **Struktur bevares**: Lister, paragraffer og andre HTML elementer
- **Intelligent tekstekstraktion**: Kun selve teksten oversættes, ikke HTML tagsrekte i Elementor editoren ved hjælp af DeepL API.

## Beskrivelse

Dette plugin tilføjer oversættelsesfunktionalitet direkte til Elementor editoren, så du kan oversætte tekst i dine widgets med et enkelt klik. Pluginet understøtter aktuelt Heading, Text Editor og Button widgets.

## Features

- 🌐 Inline oversættelse direkte i Elementor editoren
- 🤖 Powered by DeepL API for høj kvalitets oversættelser
- 🎯 Understøtter Heading, Text Editor og Button widgets
- 🔧 Nem integration med eksisterende Elementor workflow
- 🛡️ Built-in sikkerhedsforanstaltninger og nonce verification
- 📝 **HTML-formatering bevares** automatisk ved oversættelse af Text Editor widgets
- ⚡ Real-time preview opdateringer med øjeblikkelig visuel feedback

## Krav

- WordPress 5.0 eller højere
- Elementor 3.5.0 eller højere
- PHP 7.4 eller højere
- DeepL API nøgle (gratis eller Pro)

## Installation

1. Download plugin filerne til `/wp-content/plugins/elementor-inline-translate/` mappen
2. Aktiver pluginet gennem 'Plugins' menuen i WordPress
3. Konfigurer din DeepL API nøgle (se Configuration sektion)

## Mappestruktur

```
elementor-inline-translate/
├── elementor-inline-translate.php    # Hoved plugin fil
├── assets/
│   └── js/
│       └── editor.js                 # JavaScript til Elementor editor
├── includes/
│   └── class-elementor-integration.php # Elementor integration klasse
└── README.md                         # Denne fil
```

## Configuration

### DeepL API Nøgle

**Vigtigt:** Du skal konfigurere din DeepL API nøgle for at pluginet fungerer.

1. Opret en gratis eller Pro konto på [DeepL](https://www.deepl.com/pro-api)
2. Få din API nøgle fra DeepL dashboard
3. Åbn [`elementor-inline-translate.php`](elementor-inline-translate.php) 
4. Find linjen med `define( 'EIT_DEEPL_API_KEY', '...' );` (linje ~217)
5. Erstat den eksisterende nøgle med din egen

```php
define( 'EIT_DEEPL_API_KEY', 'din-deepl-api-nøgle-her' );
```

### API URL

Pluginet er konfigureret til at bruge DeepL's gratis API som standard. Hvis du har en Pro konto, kan du ændre API URL'en:

```php
// I handle_translate_text_ajax() metoden, ændr:
$api_url = 'https://api.deepl.com/v2/translate'; // For Pro
// Fra:
$api_url = 'https://api-free.deepl.com/v2/translate'; // For gratis
```

## Brug

1. Åbn Elementor editoren
2. Vælg en Heading, Text Editor eller Button widget
3. I widget indstillingerne, find "Inline Oversættelse" sektionen
4. Vælg dit ønskede målsprog fra dropdown menuen
5. Klik "Start Oversættelse" knappen
6. Teksten bliver automatisk oversat og opdateret i widget'en

### Understøttede Målsprog

- 🇩🇰 Dansk (DA)
- 🇩🇪 Tysk (DE) 
- 🇬🇧 Engelsk (EN-GB)

*Du kan nemt tilføje flere sprog ved at redigere `$options` arrayet i [`includes/class-elementor-integration.php`](includes/class-elementor-integration.php)*

## Understøttede Widgets

- **Heading Widget** - Oversætter title feltet
- **Text Editor Widget** - Oversætter editor indholdet
- **Button Widget** - Oversætter button teksten

## Tilføjelse af Nye Widgets

For at tilføje support til nye widgets, rediger [`includes/class-elementor-integration.php`](includes/class-elementor-integration.php):

```php
// Tilføj en ny hook for dit widget
add_action( 'elementor/element/widget-name/section-name/before_section_end', [ $this, 'add_translate_button_to_widget' ], 10, 2 );

// Opdater switch statement i add_translate_button_to_widget metoden
case 'dit-widget-navn':
    $text_control_name = 'dit-tekst-felt-navn';
    break;
```

Opdater også JavaScript i [`assets/js/editor.js`](assets/js/editor.js) for at håndtere det nye widget:

```javascript
case 'dit-widget-navn':
    textToTranslate = getSetting('dit-tekst-felt-navn');
    textFieldKey = 'dit-tekst-felt-navn';
    break;
```

## Udvikling

### Debug Mode

Pluginet inkluderer omfattende debug logging. Tjek din WordPress debug log for meddelelser der starter med "EIT Debug:" for at troubleshoot problemer.

### JavaScript Events

Pluginet lytter efter custom events i Elementor editoren:
- `eit:translate` - Trigger oversættelse
- Standard click events på `.eit-translate-button` klassen

### AJAX Endpoints

- `wp_ajax_eit_translate_text` - Håndterer oversættelse requests

## Fejlfinding

### Almindelige Problemer

1. **Knappen vises ikke**
   - Tjek at Elementor er aktiveret og opdateret
   - Kontroller browser console for JavaScript fejl

2. **Oversættelse fejler**
   - Verificer at din DeepL API nøgle er korrekt
   - Tjek din DeepL konto for kvote begrænsninger
   - Se WordPress debug log for detaljerede fejlmeddelelser

3. **Tekst opdateres ikke**
   - Prøv at refreshe Elementor editoren
   - Tjek browser console for JavaScript fejl

### Debug Logging

Aktiver WordPress debug logging ved at tilføje følgende til din `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Sikkerhed

- Alle AJAX requests er sikret med WordPress nonce
- Input data saniteres før brug
- API nøgler bør gemmes sikkert (overvej at bruge WordPress options eller konstanter)

## Bidrag

Bidrag er velkomne! Åbn gerne issues eller submit pull requests for forbedringer.

## Licens

Dette plugin er udviklet som et demo/eksempel projekt. Brug på eget ansvar.

## Support

For support eller spørgsmål, kontakt udvikleren gennem [plugin support kanaler].

## Changelog

### 1.0.0
- Initial release
- Support for Heading, Text Editor og Button widgets
- DeepL API integration
- Dansk, Tysk og Engelsk sprog support

---

**Bemærk:** Dette plugin kræver en gyldig DeepL API nøgle for at fungere. DeepL API har gratis og betalte planer tilgængelige.