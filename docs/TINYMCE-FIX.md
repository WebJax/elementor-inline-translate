# TinyMCE HTML Rendering Fix

## Problem Beskrivelse

Tidligere viste TinyMCE-editoren oversatte HTML-indhold som ren tekst i stedet for at rendere HTML-formateringen korrekt i den visuelle editor. Dette skete fordi:

1. **Editor Mode Issues**: TinyMCE kan være i "visual" eller "text" mode, og HTML skulle håndteres forskelligt
2. **Timing Problems**: TinyMCE-editoren var ikke altid fuldt initialiseret, når vi prøvede at sætte indholdet
3. **Event Triggering**: Ikke alle nødvendige events blev trigget for at sikre korrekt opdatering af modellen

## Løsning

### 1. Ny Hjælpefunktion: `setTinyMCEContent()`

Oprettede en robust hjælpefunktion, der håndterer alle aspekter af TinyMCE-indholdssætning:

```javascript
function setTinyMCEContent(editor, content, fallbackElement) {
    return new Promise((resolve, reject) => {
        // Comprehensive TinyMCE content setting with proper HTML rendering
    });
}
```

**Funktioner:**
- **HTML Format Specification**: Explicit `{format: 'html'}` parameter
- **Visual Mode Enforcement**: Sikrer at editoren er i visual mode for HTML-rendering
- **Multiple Event Triggering**: Trigger flere events for korrekt model-opdatering
- **Initialization Handling**: Venter på editor-initialisering hvis nødvendigt
- **Fallback Mechanism**: Bruger textarea som fallback hvis TinyMCE fejler
- **Promise-based**: Asynkron håndtering med proper error handling

### 2. Forbedret HTML-rendering

**Før:**
```javascript
editor.setContent(content);
editor.fire('change');
```

**Efter:**
```javascript
editor.setContent(content, {format: 'html'});
if (editor.isHidden()) editor.show();
if (editor.mode && editor.mode.get() === 'text') editor.mode.set('design');
editor.fire('change');
editor.fire('input');
editor.fire('keyup');
editor.fire('ExecCommand', {command: 'mceInsertContent', value: ''});
editor.save();
```

### 3. Forbedret Error Handling

- **Try-catch blocks** omkring alle TinyMCE-operationer
- **Promise-based** asynkron håndtering
- **Timeout fallbacks** for langsom initialisering
- **Automatic textarea fallback** hvis TinyMCE fejler

## Test Scenariet

### Test 1: Oversættelse af HTML-formateret Tekst

1. **Opret Text Editor Widget** med HTML-indhold:
   ```html
   <p>Dette er <strong>fed tekst</strong> og <em>kursiv tekst</em>.</p>
   <ul>
   <li>Punkt 1</li>
   <li>Punkt 2</li>
   </ul>
   ```

2. **Oversæt indholdet** ved at klikke på translate-knappen

3. **Forventet resultat**: 
   - HTML-formateringen bevares i oversættelsen
   - TinyMCE viser oversættelsen med korrekt formatering (fed, kursiv, lister)
   - Ingen rå HTML-koder vises i visual mode

### Test 2: Copy Reference med HTML

1. **På hovedsproget**: Opret Text Editor med HTML-formatering
2. **På oversættelse**: Brug "Copy from Reference" knappen
3. **Forventet resultat**: HTML kopieres og vises korrekt formateret

### Test 3: Kompleks HTML-strukturer

Test med:
- Links: `<a href="#">Link tekst</a>`
- Images: `<img src="..." alt="...">`
- Tabeller
- Nested HTML-strukturer

## Tekniske Forbedringer

### 1. Editor State Management

```javascript
// Check if editor is ready
if (editor.initialized && !editor.removed) {
    setContent();
} else if (!editor.removed) {
    // Wait for initialization
    editor.on('init', initHandler);
}
```

### 2. Mode Detection og Switching

```javascript
// Ensure visual mode for HTML rendering
if (editor.isHidden()) {
    editor.show();
}

// Switch to visual mode if in text mode
if (editor.mode && editor.mode.get() === 'text') {
    editor.mode.set('design');
}
```

### 3. Comprehensive Event Triggering

```javascript
editor.fire('change');
editor.fire('input');
editor.fire('keyup');
editor.fire('ExecCommand', {command: 'mceInsertContent', value: ''});
editor.save();
```

## Debugging

Hvis HTML stadig ikke renderes korrekt:

1. **Check Browser Console** for TinyMCE errors
2. **Verify Editor Mode**: Sørg for at editoren er i visual mode
3. **Check Editor Initialization**: Se om `editor.initialized` er `true`
4. **Inspect HTML Content**: Verify at HTML-indholdet er gyldigt

## Kompatibilitet

Løsningen er kompatibel med:
- **TinyMCE 4.x og 5.x**
- **Elementor Pro og Free**
- **Alle WordPress-versioner** der understøtter Elementor
- **Alle browser** der understøtter TinyMCE

## Yderligere Forbedringer

For fremtidige versioner kunne vi tilføje:

1. **Content Validation**: Validere HTML-indhold før indsættelse
2. **Custom TinyMCE Plugins**: Specialiserede plugins til oversættelse
3. **Visual Diff**: Vise forskelle mellem original og oversættelse
4. **Undo/Redo Support**: Bedre integration med TinyMCE's undo-system
