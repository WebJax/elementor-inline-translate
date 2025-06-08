# Test Guide - HTML Formatering Bevarelse

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

## 🔍 Debug Information
- Åbn browser udviklerværktøjer (F12)
- Se Console tab for "EIT Debug:" beskeder
- Tjek at der ikke er JavaScript fejl
- I WordPress debug log, se efter "EIT Debug:" beskeder

## ✅ Success Indikatorer
- Grøn toast notification: "Tekst er blevet oversat med bevarelse af HTML formatering!"
- Preview opdateres øjeblikkeligt
- HTML struktur og styling bevares
- Ingen JavaScript console fejl
