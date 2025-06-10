# Test Guide - HTML Formatering Bevarelse og PolyLang Integration

## 🧪 Test Scenarie 1: Text Editor Widget med HTML
1. Åbn Elementor editoren
2. Tilføj en Text Editor widget
3. Indtast formateret tekst som f.eks.:
   ```html
   <p>Dette er <strong>fed tekst</strong> og <em>kursiv tekst</em>.</p>
   <ul>
   <li>Punkt 1 med <a href="https://example.com">et link</a></li>
   <li>Punkt 2</li>
   </ul>
   ```
4. Vælg widget'en i editoren
5. Scroll ned til "Inline Oversættelse" sektionen
6. Vælg målsprog (f.eks. Engelsk)
7. Klik "Start Oversættelse"
8. **Forventet resultat**: Teksten oversættes, men HTML tags og formatering bevares

## 🧪 Test Scenarie 2: Heading Widget (simpel tekst)
1. Tilføj en Heading widget
2. Indtast: "Velkommen til vores hjemmeside"
3. Oversæt til engelsk
4. **Forventet resultat**: Simpel tekstoversættelse uden HTML

## 🧪 Test Scenarie 3: Button Widget
1. Tilføj en Button widget
2. Sæt button tekst til: "Læs mere her"
3. Oversæt til engelsk
4. **Forventet resultat**: Button tekst ændres til "Read more here"

## 🌐 Test Scenarie 4: PolyLang Integration (hvis PolyLang er aktivt)

### Forudsætninger:
- PolyLang plugin skal være installeret og aktiveret
- Der skal være oprettet mindst to sprog (f.eks. Dansk som standard og Engelsk)
- Der skal findes en side på hovedsproget med Elementor indhold

### Test Steps:
1. **Opret side på hovedsprog**:
   - Opret en ny side på standardsproget (f.eks. Dansk)
   - Tilføj Elementor indhold med Heading, Text Editor og Button widgets
   - Gem siden

2. **Opret oversættelse**:
   - Opret en oversættelse af siden via PolyLang
   - Åbn oversættelsen i Elementor editoren

3. **Test reference tekst visning**:
   - Vælg en widget (f.eks. Heading)
   - I "Inline Oversættelse" sektionen, se at der vises:
     - Reference tekst felt med tekst fra hovedsproget
     - "Kopier fra hovedsprog" knap
     - "Start Oversættelse" knap

4. **Test kopier fra reference**:
   - Klik "Kopier fra hovedsprog" knappen
   - **Forventet resultat**: Reference teksten kopieres til det aktuelle felt

5. **Test automatisk reference indlæsning**:
   - Vælg forskellige widgets
   - **Forventet resultat**: Reference tekst indlæses automatisk for hver widget

## 🔍 Debug Information
- Åbn browser udviklerværktøjer (F12)
- Se Console tab for "EIT Debug:" beskeder
- Tjek at der ikke er JavaScript fejl
- I WordPress debug log, se efter "EIT Debug:" beskeder
- Tjek Network tab for AJAX requests til `eit_get_reference_text`

## 🖥️ TinyMCE HTML Rendering Tests

### Test 1: Kompleks HTML Oversættelse
1. **Opret Text Editor Widget** med følgende HTML:
   ```html
   <p>Dette er <strong>fed tekst</strong> og <em>kursiv tekst</em>.</p>
   <ul>
   <li>Første punkt</li>
   <li>Andet punkt med <a href="https://example.com">link</a></li>
   </ul>
   <blockquote>Dette er et citat med <code>kode</code></blockquote>
   ```

2. **Oversæt indholdet**:
   - Klik "Start Oversættelse" 
   - **Forventet resultat**: 
     - HTML structure bevares
     - Fed tekst, kursiv, lister og links renderes korrekt i TinyMCE
     - Ingen rå HTML-koder vises i visual mode

### Test 2: TinyMCE Visual Mode Verification
1. **Åbn Text Editor** i Elementor
2. **Verificer TinyMCE mode**:
   - Tjek at TinyMCE er i visual mode (ikke text mode)
   - Se at HTML-indholdet renderes som formateret tekst
3. **Oversæt indholdet**
4. **Kontroller efter oversættelse**:
   - TinyMCE forbliver i visual mode
   - Oversættelsen vises som formateret tekst, ikke rå HTML

### Test 3: Editor Initialization Timing
1. **Åbn ny Elementor side**
2. **Tilføj Text Editor widget**
3. **Oversæt øjeblikkeligt** (test timing issues)
4. **Forventet resultat**: 
   - Oversættelse fungerer også hvis TinyMCE ikke er fuldt initialiseret
   - Fallback til textarea hvis nødvendigt

### Test 4: Copy Reference med HTML
1. **På hovedsprog**: Opret Text Editor med HTML-formatering
2. **På oversættelse**: Vælg samme Text Editor widget
3. **Klik "Kopier fra hovedsprog"**
4. **Forventet resultat**: 
   - HTML kopieres korrekt
   - TinyMCE viser HTML som formateret indhold
   - Ingen performance problemer

## 🔍 Debug Information
- Åbn browser udviklerværktøjer (F12)
- Se Console tab for "EIT Debug:" beskeder
- Tjek at der ikke er JavaScript fejl
- I WordPress debug log, se efter "EIT Debug:" beskeder
- Tjek Network tab for AJAX requests til `eit_get_reference_text`

## ✅ Success Indikatorer

### Generel oversættelse:
- Grøn toast notification: "Tekst er blevet oversat med bevarelse af HTML formatering!"
- Preview opdateres øjeblikkeligt
- HTML struktur og styling bevares
- Ingen JavaScript console fejl

### TinyMCE HTML Rendering:
- HTML-indhold vises som formateret tekst (ikke rå HTML koder)
- TinyMCE forbliver i visual mode efter oversættelse
- Alle HTML-elementer (fed, kursiv, links, lister) renderes korrekt
- Console log viser: "EIT Debug: Successfully updated TinyMCE content with HTML rendering"

### PolyLang integration:
- Reference tekst vises korrekt for oversættelsessider
- "Kopier fra hovedsprog" knap fungerer
- Grøn toast: "Reference tekst kopieret!"
- Reference tekst indlæses automatisk når widget vælges

## ❌ Fejl at være opmærksom på

### Generelle fejl:
- Reference felter vises ikke på hovedsprog sider (dette er korrekt adfærd)
- Reference felter vises ikke hvis PolyLang ikke er aktivt
- Fejlbeskeder hvis hovedsprog side ikke findes
- AJAX fejl hvis post ID ikke kan bestemmes

### TinyMCE specifikke fejl:
- **HTML vises som rå tekst**: Indikerer TinyMCE rendering problem
- **Editor ikke opdateret**: TinyMCE events trigges ikke korrekt
- **Fallback beskeder**: "Used textarea fallback" indikerer TinyMCE utilgængelighed
- **Timing fejl**: "TinyMCE init timeout" indikerer langsom editor initialization

### Troubleshooting TinyMCE problemer:
1. **Tjek console logs** for TinyMCE fejl
2. **Verificer editor state**: `editor.initialized` skal være `true`
3. **Kontroller editor mode**: TinyMCE skal være i visual mode
4. **Test manuel indhold**: Prøv at sætte indhold manuelt i editoren
5. **Reload Elementor**: Genindlæs editoren hvis problemer fortsætter
