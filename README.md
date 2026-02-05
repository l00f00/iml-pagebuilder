# IML Page Builder

Plugin personalizzato per la gestione dei contenuti e del layout del sito IML Photographer.

## Funzionalità Principali

### 1. Custom Post Types (CPT)
Registra e gestisce i seguenti tipi di contenuto personalizzati:
*   **Portfolio:** Gerarchico, supporta categorie e tag.
*   **Serie:** Non gerarchico, supporta categorie e tag.
*   **Progetti:** Non gerarchico, supporta categorie e tag.

### 2. Page Builder & Meta Boxes
Implementa un sistema di **Drag & Drop** personalizzato per costruire griglie di contenuti:
*   **Homepage Builder:** Permette di selezionare Progetti, Portfolio, Serie o Foto e organizzarli in una griglia personalizzata direttamente dalla pagina impostata come Front Page.
*   **Portfolio & Project Builder:** Permette di associare e ordinare gallerie di immagini o altri post all'interno dei singoli Progetti o Portfolio.
*   **Opzioni di Layout:** Per ogni elemento della griglia è possibile definire l'allineamento (Alto, Basso, Sinistra, Destra).

### 3. Gestione Allegati (Attachments)
Estende le funzionalità della libreria media di WordPress:
*   **Campi Custom:** Aggiunge campi per "Anno", "Allineamento Singolo" e checkbox "Ha Pagina Singola".
*   **Tag:** Abilita i tag standard di WordPress anche per gli allegati.

### 4. Ottimizzazione Admin
*   **Pulizia Interfaccia:** Rimuove menu non necessari (es. "Articoli", "Commenti") per semplificare l'esperienza dell'amministratore.
*   **Disabilitazione Commenti:** Disabilita globalmente i commenti e i trackback.

### 5. Frontend & Utility
*   **Gestione Redirect:** Include logiche di redirect personalizzate per utenti loggati/non loggati.
*   **Integrazione Lightbox:** Carica e configura Simple Lightbox per la visualizzazione delle immagini.
*   **AJAX:** Gestisce il caricamento asincrono dei post per le griglie dinamiche.

## Installazione
1.  Scaricare o clonare la cartella del plugin in `wp-content/plugins/`.
2.  Attivare il plugin "IML Page Builder" dal pannello di amministrazione di WordPress.
3.  Assicurarsi che il plugin **Meta Box** sia installato e attivo (richiesto per alcune funzionalità).

## Struttura
*   `index.php`: Entry point.
*   `includes/`: Contiene tutta la logica divisa in moduli.
*   `old/`: Archivio del codice legacy.

## Webhook Test
*   Test di verifica webhook: OK.

## TODO:
*   Testare l'integrazione con Oxygen Builder.
*   Verificare la compatibilità con altri plugin.
*   in progetto posttype .left-column-progetto a img {
    max-height: 100%;
    height: auto;
    width: -webkit-fill-available;
    top: 0;
    left: 0;
    object-fit: contain;
    width: -webkit-fill-available;
}
