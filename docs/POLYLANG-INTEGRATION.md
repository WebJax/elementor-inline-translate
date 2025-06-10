# PolyLang Integration Implementeringsguide

## 🎯 Oversigt

PolyLang integrationen til Elementor Inline Translate plugin gør det muligt at:
- Vise reference tekst fra hovedsprogets version af elementer
- Kopiere tekst direkte fra reference med et klik
- Automatisk indlæse reference tekst når elementer vælges

## 🔧 Teknisk Implementation

### Server-side funktionaliteter

#### Hovedklassen (elementor-inline-translate.php)

1. **PolyLang detection**:
   ```php
   public function is_polylang_active()
   public function get_default_language()
   public function is_current_page_translation()
   ```

2. **AJAX handler for reference tekst**:
   ```php
   public function handle_get_reference_text_ajax()
   ```

3. **Rekursiv element søgning**:
   ```php
   private function find_element_text_by_id($elements, $target_id, $control_name)
   ```

#### Integration klassen (class-elementor-integration.php)

1. **Dynamiske UI controls**:
   - Reference tekst felt (kun på oversættelsessider)
   - Kopier fra reference knap
   - Forbedrede CSS klasser

### Client-side funktionaliteter

#### JavaScript (assets/js/editor.js)

1. **Event handlers**:
   ```javascript
   // Copy from reference
   $(document).on('click', '.eit-copy-reference-button', handleCopyFromReference);
   
   // Automatic reference loading
   elementor.hooks.addAction('panel/open_editor/widget', loadReferenceTextForWidget);
   ```

2. **Reference tekst funktioner**:
   ```javascript
   handleCopyFromReference()
   loadReferenceTextForWidget()
   updateReferenceTextDisplay()
   ```

#### CSS styling (assets/css/editor.css)

1. **Reference felt styling**:
   - Readonly appearance
   - Italic tekst
   - Gråt baggrund

2. **Knap styling**:
   - Blå "Kopier fra reference" knap
   - Grøn "Oversæt" knap
   - Loading states

## 🔄 Dataflow

### Reference tekst indlæsning:
1. Widget vælges i Elementor editor
2. JavaScript hook detekterer widget åbning
3. Tjekker om PolyLang er aktivt og side er oversættelse
4. AJAX request til `eit_get_reference_text` 
5. Server finder hovedsprog post ID via PolyLang
6. Henter og parser Elementor data fra hovedsprog
7. Finder matchende element og returnerer tekst
8. JavaScript opdaterer reference felt i UI

### Kopier fra reference:
1. Bruger klikker "Kopier fra reference" knap
2. AJAX request henter reference tekst
3. JavaScript kopierer tekst til target control
4. UI opdateres med samme mekanisme som oversættelse

## 🛡️ Fejlhåndtering

### Server-side:
- Validering af nødvendige parametre
- PolyLang aktivitets check
- Post eksistens validering
- Elementor data format validering

### Client-side:
- Button loading states
- Toast notifications for bruger feedback
- Console logging for debug
- Graceful degradation hvis PolyLang ikke er aktivt

## 🧪 Test Points

1. **PolyLang ikke installeret**: Reference felter vises ikke
2. **Hovedsprog side**: Reference felter vises ikke  
3. **Oversættelsesside**: Reference felter vises og fungerer
4. **Element ikke fundet**: Fejlbesked til bruger
5. **AJAX fejl**: Proper error handling og notifications

## 📝 Konfigurations muligheder

### Nemt at udvide:
- Tilføj flere widget typer i switch statements
- Tilføj flere sprog i options array
- Tilpas styling via CSS
- Udvid fejlbeskederne i i18n array

### Performance optimering:
- Reference tekst caches i `window.eitReferenceTexts`
- Debounced AJAX requests
- Conditional loading baseret på PolyLang status

## 🔮 Fremtidige forbedringer

1. **Bulk operations**: Oversæt alle elementer på siden
2. **Visual diff**: Sammenlign original og oversættelse side om side
3. **Translation memory**: Cache oversættelser på tværs af sider
4. **Custom post types**: Support for andre Elementor-enabled post types
5. **API rate limiting**: Bedre håndtering af DeepL API kvota
