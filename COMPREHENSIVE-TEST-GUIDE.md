# Test Guide - Enhanced HTML Preservation & PolyLang Integration

Dette er en omfattende test guide for at validere de kritiske forbedringer i Elementor Inline Oversættelse plugin'et, specielt HTML preservation systemet og PolyLang integration.

## 🎯 Critical Test Scenarios

### Test 1: HTML Liste (Kritisk DeepL Problem) ⚠️ HØJESTE PRIORITET

**Problem**: DeepL API har tendens til at slå listeemner sammen til en lang tekststreng, hvilket ødelægger HTML-strukturen.

**Test Steps**:
1. **Opret Text Editor Widget** med HTML liste:
   ```html
   <ul>
   <li>Første punkt i listen</li>
   <li>Andet punkt med vigtig information</li>
   <li>Tredje punkt med <strong>fed tekst</strong></li>
   </ul>
   ```

2. **Oversæt indholdet** ved at klikke på translate-knappen

3. **Valider resultat**:
   - ✅ HTML liste-struktur bevares
   - ✅ Hver `<li>` element forbliver separat
   - ✅ Ingen sammensmeltet tekst som "Første punkt Andet punkt Tredje punkt"
   - ✅ Browser inspector viser 3 separate `<li>` elementer

**Debug Logs at Tjekke**:
```
EIT Debug: Detected HTML content, preserving structure
EIT Debug: Extracted 3 text parts with separators
EIT Debug: Using separator-based reconstruction
EIT Debug: Successfully reconstructed HTML
```

### Test 2: Kompleks HTML Formatering

**Test Steps**:
1. **Opret Text Editor Widget** med blandet formatering:
   ```html
   <p>Dette er <strong>fed tekst</strong> og <em>kursiv tekst</em>.</p>
   <p>Her er en <a href="https://example.com" target="_blank">ekstern link</a>.</p>
   <ul>
   <li>Listepunkt 1</li>
   <li>Listepunkt 2 med <code>kode</code></li>
   </ul>
   <blockquote>Dette er et vigtigt citat</blockquote>
   ```

2. **Oversæt til engelsk**

3. **Valider at følgende bevares**:
   - ✅ `<strong>` og `<em>` tags med spacing
   - ✅ `<a>` tag med `href` og `target` attributter  
   - ✅ `<ul>` og `<li>` struktur
   - ✅ `<blockquote>` og `<code>` elementer
   - ✅ `<p>` paragraffer

### Test 3: TinyMCE Visual Mode Rendering

**Kritisk for brugeroplevelse**: Sikrer at oversat HTML vises korrekt i TinyMCE visual editor.

**Test Steps**:
1. **Indsæt HTML i Text Editor**:
   ```html
   <p>Velkommen til <strong>vores hjemmeside</strong>!</p>
   <p>Vi tilbyder:</p>
   <ul>
   <li>Professionel service</li>
   <li>Hurtig levering</li>
   <li>Kvalitetsgaranti</li>
   </ul>
   ```

2. **Oversæt til engelsk**

3. **Valider TinyMCE rendering**:
   - ✅ HTML vises som formateret tekst (ikke rå koder)
   - ✅ Editor forbliver i visual mode
   - ✅ Alle formateringer renderes korrekt
   - ✅ Fed tekst vises som fed (ikke som `<strong>`)
   - ✅ Lister vises med bullets

**Debug Logs at Tjekke**:
```
EIT Debug: Successfully updated TinyMCE content with HTML rendering
EIT Debug: TinyMCE content updated successfully
```

## 🔧 PolyLang Integration Tests

### Test 4: Reference Text Loading

**Forudsætninger**: 
- PolyLang aktiveret og konfigureret
- Hovedsprog post oprettet med indhold
- Oversættelse side åbnet

**Test Steps**:
1. **Vælg Text Editor widget på oversættelsesside**
2. **Tjek at reference tekst vises automatisk**:
   - ✅ Grå tekstfelt med hovedsprog indhold
   - ✅ "Kopier fra hovedsprog" knap er synlig
   - ✅ Reference tekst matcher hovedsprog præcist

3. **Test "Kopier fra hovedsprog" funktionalitet**:
   - ✅ Klik på knappen kopierer indhold
   - ✅ HTML formatering bevares ved kopiering
   - ✅ TinyMCE opdateres korrekt med formateret indhold

**Debug Logs at Tjekke**:
```
EIT Debug: Reference text loaded for control: editor
EIT Debug: Loading reference text for widget
EIT Debug: Found reference text: [content]
```

### Test 5: Cross-Language Content Sync

**Test Steps**:
1. **Opret indhold på hovedsprog** (f.eks. dansk):
   ```html
   <h2>Sektion Titel</h2>
   <p>Dette er vores <strong>fantastiske</strong> hjemmeside med:</p>
   <ul>
   <li>Professionel design</li>
   <li>Hurtig performance</li>
   </ul>
   ```

2. **Naviger til oversættelsesside** (f.eks. engelsk)
3. **Åbn samme Text Editor widget**
4. **Valider reference text matching**:
   - ✅ Reference tekst vises automatisk
   - ✅ HTML formatering preserved i reference
   - ✅ Korrekt indhold fra hovedsprog

## 📊 Performance og Stabilitet Tests

### Test 6: Store HTML Strukturer

**Scenario**: Test plugin'ets ydeevne med komplekst indhold.

**Test Steps**:
1. **Opret stort HTML indhold** (minimum 20 paragraffer):
   ```html
   <div>
   <h2>Sektion 1</h2>
   <p>Lorem ipsum med <strong>fed tekst</strong> og <em>kursiv</em>.</p>
   <ul>
   <li>Punkt 1</li>
   <li>Punkt 2</li>
   <li>Punkt 3</li>
   </ul>
   <!-- Gentag struktur 10+ gange -->
   </div>
   ```

2. **Mål oversættelsestid**
3. **Valider performance**:
   - ✅ Oversættelse fuldføres på under 10 sekunder
   - ✅ Alle HTML tags bevares intakte
   - ✅ Browser forbliver responsiv
   - ✅ Ingen memory leaks eller JavaScript fejl

### Test 7: Edge Cases og Fejlhåndtering

**Test forskellige edge cases**:

1. **Tom HTML tags**: 
   ```html
   <p></p><div><span></span></div><p>Tekst efter tomme tags</p>
   ```

2. **Nested formatting**: 
   ```html
   <p><strong><em>Multiple levels</em></strong> normal tekst</p>
   ```

3. **Special characters**: 
   ```html
   <p>Æ, Ø, Å og €-tegn med <strong>formatering</strong></p>
   ```

4. **Mixed content**: 
   ```html
   Normal tekst <strong>fed</strong> mere tekst <em>kursiv</em> afslutning.
   ```

**Valider at**:
- ✅ Alle edge cases håndteres gracefully
- ✅ Fejl ikke crasher systemet
- ✅ Fallback mechanisms fungerer

### Test 8: DeepL API Failure Simulation

**Test Steps**:
1. **Disconnect internet midlertidigt**
2. **Forsøg oversættelse**
3. **Valider error handling**:
   - ✅ Brugervenlinge fejlbesked vises
   - ✅ Plugin forbliver stable
   - ✅ Ingen JavaScript crashes

## 🔍 Debug Information & Monitoring

### Browser Console Logs

**Vigtige logs at tjekke**:
```javascript
EIT Debug: Elementor init event detected
EIT Debug: Starting translation process
EIT Debug: Detected HTML content, preserving structure
EIT Debug: Extracted X text parts with separators
EIT Debug: Using separator-based reconstruction
EIT Debug: Successfully reconstructed HTML
EIT Debug: TinyMCE content updated successfully
```

### WordPress Debug Logs

**Server-side logs**:
```php
EIT Debug: HTML content detected for preservation
EIT Debug: Extracted text for translation: [text with separators]
EIT Debug: Translation response received
EIT Debug: Reconstructed HTML with translated text
```

### Network Tab Monitoring

**AJAX Requests at overvåge**:
- `eit_translate_text` - Hovedoversættelse request
- `eit_get_reference_text` - PolyLang reference loading
- DeepL API calls (hvis synlige)

## ✅ Success Indikatorer Checklist

### Kritiske Success Metrics:

**HTML Preservation**:
- [ ] Liste struktur bevares (3/3 `<li>` elementer)
- [ ] Inline formatering bevares (`<strong>`, `<em>`)
- [ ] Link attributter bevares (`href`, `target`)
- [ ] Nested elementer håndteres korrekt
- [ ] Special characters behandles korrekt

**TinyMCE Rendering**:
- [ ] HTML vises som formateret tekst
- [ ] Editor forbliver i visual mode
- [ ] Ingen rå HTML koder synlige
- [ ] Spacing omkring elementer er korrekt

**PolyLang Integration**:
- [ ] Reference tekst loader automatisk
- [ ] "Kopier fra hovedsprog" fungerer
- [ ] Cross-language sync er præcis
- [ ] Performance er acceptabel

**Error Handling**:
- [ ] Graceful degradation ved fejl
- [ ] Brugervenlinge fejlbeskeder
- [ ] Ingen JavaScript crashes
- [ ] Fallback mechanisms virker

## 🚨 Kritiske Fejl at Være Opmærksom På

### HTML Corruption Signs:
- ❌ Liste elementer bliver til "Item1 Item2 Item3"
- ❌ `<strong>bold</strong>` bliver til rå tekst
- ❌ Links mister `href` attributter
- ❌ Nested HTML bliver flattened

### TinyMCE Problems:
- ❌ Rå HTML koder vises i editor
- ❌ Editor skifter til text mode
- ❌ Formatering forsvinder visuelt
- ❌ Editor bliver uresponsiv

### PolyLang Issues:
- ❌ Reference tekst vises ikke
- ❌ "Kopier fra hovedsprog" gør ingenting
- ❌ AJAX fejl i console
- ❌ Forkert reference indhold

## 💡 Troubleshooting Guide

### Hvis HTML strukturen går tabt:
1. Tjek console for "All reconstruction methods failed"
2. Valider at DOMDocument er tilgængelig på serveren
3. Tjek DeepL API response format
4. Verify separator preservation i logs

### Hvis TinyMCE viser rå HTML:
1. Tjek for JavaScript fejl i console
2. Valider TinyMCE initialization state
3. Se efter timing issues
4. Test manual content setting

### Hvis PolyLang integration fejler:
1. Verify PolyLang activation og konfiguration
2. Tjek hovedsprog post eksistens
3. Monitor AJAX requests for errors
4. Validate post ID detection

Dette test guide sikrer omfattende validering af alle kritiske funktioner i det opdaterede HTML preservation system.
