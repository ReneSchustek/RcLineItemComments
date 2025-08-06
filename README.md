# LineItemComments Plugin - Installations- und Nutzungsanleitung

## Übersicht
Das LineItemComments Plugin ermöglicht es Kunden, Pflicht-Bemerkungen zu einzelnen Artikeln im Warenkorb hinzuzufügen. 
Jeder Artikel erfordert eine individuelle Bemerkung, auch wenn mehrere Exemplare desselben Produkts bestellt werden.

## Funktionen
- Pflicht-Bemerkungen für jeden einzelnen Artikel im Warenkorb
- Automatisches Speichern der Kommentare während der Eingabe
- Validierung vor dem Checkout-Prozess
- Anzeige der Kommentare auf der Checkout-Bestätigungsseite
- Übertragung der Kommentare in die finale Bestellung
- Admin-Bereich Integration (Anzeige der Kommentare in Bestelldetails)
- Kategorie-Einstellungen im Admin
- Mehrsprachigkeit (Deutsch/Englisch)

## Kategorie-Konfiguration

### Im Admin-Bereich konfigurieren:
1. **Kataloge → Kategorien** aufrufen
2. **Gewünschte Kategorie** auswählen
3. Im Tab **"Custom Fields"** oder **"Zusatzfelder"**
4. **"Bemerkungen für Artikel erforderlich"** aktivieren
5. **Speichern**

### Funktionsweise:
- **Nur Produkte** aus aktivierten Kategorien erfordern Kommentare
- **Alle anderen Produkte** benötigen keine Bemerkungen
- **Vererbung**: Unterkategorien erben die Einstellung nicht automatisch
- **Mehrere Kategorien**: Ist ein Produkt in mehreren Kategorien und mindestens eine erfordert Kommentare, ist das Kommentarfeld Pflicht

## Installation

### Schritt 1: Plugin-Dateien hochladen
1. Alle Dateien in das Verzeichnis `custom/plugins/LineItemComments/` hochladen
2. Sicherstellen, dass die Ordnerstruktur korrekt ist:
```
custom/plugins/LineItemComments/
├── composer.json
├── src/
│   ├── LineItemComments.php
│   ├── Migration/
│   ├── Core/
│   ├── Service/
│   ├── Subscriber/
│   ├── Storefront/
│   └── Resources/

### Schritt 2: Plugin installieren
```bash
# In das Shopware-Root-Verzeichnis wechseln
cd /path/to/shopware

# Plugin installieren
bin/console plugin:refresh
bin/console plugin:install --activate LineItemComments

# Datenbank-Migration ausführen
bin/console database:migrate --all LineItemComments

# Cache leeren
bin/console cache:clear
```

### Schritt 3: Assets kompilieren (optional)
Falls eigene Assets verwendet werden:
```bash
# Storefront Assets kompilieren
bin/console bundle:dump
./bin/build-storefront.sh
```

### Schritt 4: Plugin-Konfiguration
Das Plugin funktioniert nach der Installation sofort ohne weitere Konfiguration.

## Verwendung

### Für Kunden
1. **Artikel in Warenkorb legen**: Normale Produktauswahl
2. **Warenkorb aufrufen**: Unter jedem Artikel erscheint ein Pflicht-Kommentarfeld
3. **Kommentare eingeben**: Jeder Artikel benötigt eine individuelle Bemerkung
4. **Auto-Save**: Kommentare werden automatisch nach 1 Sekunde gespeichert
5. **Checkout**: Validierung verhindert Fortfahren ohne vollständige Kommentare
6. **Bestellung**: Kommentare werden in der finalen Bestellung gespeichert

### Für Shop-Betreiber
1. **Bestellverwaltung**: Kommentare sind in den Bestelldetails im Admin-Bereich sichtbar
2. **Kundenservice**: Alle Kommentare bleiben dauerhaft in der Bestellung gespeichert

## Anpassungen

### Templates anpassen
Die Templates können über das Theme-System überschrieben werden:
- Cart: `@LineItemComments/storefront/component/line-item/line-item.html.twig`
- Checkout: `@LineItemComments/storefront/page/checkout/confirm/index.html.twig`

### Styling anpassen
CSS-Klassen für individuelle Anpassungen:
- `.line-item-comment-container`: Container um Kommentarfeld
- `.line-item-comment`: Das Textfeld selbst
- `.checkout-confirm-item-comment`: Kommentar-Anzeige im Checkout

### Übersetzungen anpassen
Übersetzungen in folgenden Dateien anpassen:
- `src/Resources/snippet/de-DE/messages.de-DE.json`
- `src/Resources/snippet/en-GB/messages.en-GB.json`

### JavaScript-API erweitern
Das Plugin bietet eine JavaScript-API:
```javascript
// Plugin-Instanz abrufen
const commentsPlugin = window.PluginManager.getPluginInstanceFromElement(
    document.querySelector('[data-line-item-comments]'),
    'LineItemComments'
);

// Alle Kommentare validieren
const isValid = commentsPlugin.validateComments();

// Alle Kommentare speichern
commentsPlugin.saveAllComments();
```

## Fehlerbehebung

### Plugin wird nicht geladen
```bash
# Plugin-Status prüfen
bin/console plugin:list

# Plugin deaktivieren und neu aktivieren
bin/console plugin:deactivate LineItemComments
bin/console plugin:activate LineItemComments
bin/console cache:clear
```

### Datenbank-Probleme
```bash
# Migration-Status prüfen
bin/console database:migrate:status

# Spezifische Migration ausführen
bin/console database:migrate --all LineItemComments
```

### Frontend-Probleme
```bash
# Assets neu kompilieren
bin/console bundle:dump
./bin/build-storefront.sh
bin/console cache:clear
```

### JavaScript-Fehler
1. Browser-Konsole auf Fehler prüfen
2. Sicherstellen, dass jQuery/Bootstrap verfügbar ist
3. Plugin-Registrierung in `main.js` prüfen

## Erweiterte Konfiguration

### Pflichtfeld-Validierung anpassen
In `LineItemCommentService.php` die `validateCartComments` Methode anpassen:
```php
// Beispiel: Minimale Zeichenanzahl für Kommentare
if (strlen(trim($comment)) < 10) {
    $errors[$lineItem->getId()] = 'Kommentar muss mindestens 10 Zeichen haben';
}
```

### Auto-Save-Intervall ändern
In `line-item-comments.plugin.js`:
```javascript
static options = {
    // ... andere Optionen
    autoSaveDelay: 2000 // 2 Sekunden statt 1
};
```

### Zusätzliche Validierungsregeln
Neue Event-Subscriber erstellen und in `services.xml` registrieren.

## Kompatibilität
- **Shopware Version**: 6.7.x
- **PHP**: ≥ 8.1
- **MySQL**: ≥ 5.7
- **Browser**: Moderne Browser mit ES6+ Support

## Support und Updates

### Logging aktivieren
```php
// In LineItemCommentService.php
use Psr\Log\LoggerInterface;

private LoggerInterface $logger;

public function saveComment(string $lineItemId, string $comment, Context $context): void
{
    $this->logger->info('Saving comment for line item', [
        'lineItemId' => $lineItemId,
        'comment' => substr($comment, 0, 50) . '...'
    ]);
    // ... Rest der Methode
}
```

### Performance-Optimierung
- Für viele gleichzeitige Benutzer: Redis-Cache für temporäre Kommentare verwenden
- Auto-Save-Intervall erhöhen bei langsameren Systemen
- Kommentar-Länge begrenzen (z.B. maximal 1000 Zeichen)

## Deinstallation
```bash
# Plugin deaktivieren
bin/console plugin:deactivate LineItemComments

# Plugin deinstallieren (behält Daten)
bin/console plugin:uninstall LineItemComments

# Plugin komplett entfernen (löscht Daten)
bin/console plugin:uninstall --keep-user-data=false LineItemComments

# Dateien manuell löschen
rm -rf custom/plugins/LineItemComments/
```

## Changelog

### Version 1.0.0
- Erste Veröffentlichung
- Pflicht-Kommentare für Line Items
- Auto-Save Funktionalität
- Admin-Integration
- Mehrsprachigkeit (DE/EN)

## Lizenz
MIT License - Siehe composer.json für Details

## Autor
Plugin entwickelt für Shopware 6.7.x
Kontakt: info@ruhrcoder.de
