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
*   `frontend/`: Contiene CSS e JS per il frontend.
*   `old/`: Archivio del codice legacy.

## Changelog & Status

### FATTO (Done)
*   **Migrazione Codice Admin:** Tutto il codice originale (`all_admin_code.php`) è stato migrato in una struttura modulare (`includes/`).
*   **Integrazione Registrazione Campi:** Le registrazioni dei CPT e Meta Box (`registrazionecampi.php`) sono state integrate in `includes/cpt-registrations.php`.
*   **Migrazione Frontend:** 
    *   La logica homepage è ora nello shortcode `[iml_homepage_grid]`.
    *   La logica portfolio single è ora nello shortcode `[iml_portfolio_grid]`.
    *   La logica archivio (tag/cat) è ora nello shortcode `[iml_archive_grid]`.
    *   La logica archivio generico è ora nello shortcode `[iml_generic_archive_grid]`.
    *   La logica serie/progetto single è ora nello shortcode `[iml_serie_single]`.
    *   La logica attachment single è ora nello shortcode `[iml_attachment_single]`.
*   **Assets Frontend:** CSS e JS sono stati separati in file dedicati (`frontend/style.css`, `frontend/script.js`) e vengono caricati correttamente.
*   **Webhook:** Configurazione e test webhook GitHub completato.
*   **Bug Builder Home:** Risolvere bug builder pagina HOME (spostare builder animazione o soluzione in place, ottimizzazione caricamento).
*   **Builder Dropdown:** Dividere righe dropdown builder in 3 colonne per esperienza piu' friendly.
*   **Ottimizzazione Immagini:** Ottimizzazione Immagini che impediscono il caricamento del builder.
*   **Backend Upload:** Risolto bug upload da Media non funzionante su Portfolio (conflitto ID e inizializzazione array vuoto).
*   **Safari CSS:** Risolto bug foto "cover" progetti esplode su Safari (aggiunto `width: -webkit-fill-available!important`).

### TODO (Elenco lavorazioni in sospeso)

#### Priorità Alta & Bug
*   [ ] **Home Builder:** Risolvere bug builder pagina HOME (spostare builder animazione in nuova pagina con label apposito).
*   [ ] **Home Intro:** Animazione nome in INTRO — (In attesa file da cartella drive).
*   [ ] **Home Preloading:** Inserimento animazione preloading pagina.
*   [ ] **Home Animazione:** Disaccoppiare animazione rompere stage entrata cornice di nomi.

*   [ ] **Foto Cover Contain:** Applicare regola: fit orizzontale foto cover progetto  in left column non si deve sovrapporre al testo.
    ```css
    .left-column-progetto img { 
         max-height: 100%; 
         height: auto; 
         height: -webkit-fill-available; 
         top: 0; 
         left: 0; 
         object-fit: contain; 
         width: -webkit-fill-available; 
    }
    ```

#### Formattazione / Layout
*   [ ] **Menu Desktop:** Allineare menu titoli con menu tendina (allineare a fine parola, bandiera allineata a fine parola titolo).
*   [ ] **Menu Desktop:** Allineare con testi (valutare fattibilità tecnica).
*   [ ] **Menu Mobile:** Aggiungere una piccola "V" per segnare apertura sottomenu al click.
*   [ ] **Testi Progetti:** Implementare "Read More" per testi lunghi (desktop e valutare tablet).
*   [ ] **Foto Cover:** Applicare regola: verticale fit 100vh, orizzontale fit container.
*   [ ] **Foto Cover Orizzontale:** Gestione specifica nei progetti (es. Jhalak, Termini Underground).
*   [ ] **Allineamento Foto:** Allineare foto "cover" orizzontale quando a dx c’è foto orizzontale (es. COEZ).
*   [ ] **Cross-browser:** Fare check sui vari browser.

#### Mobile
*   [ ] **Menu:** Finisce troppo in basso e diventa non cliccabile.
*   [ ] **Progetti:** Testo troppo a filo.
*   [ ] **Bug Scroll:** Pagina singola progetto cambia da sola allo scroll touch.
*   [ ] **Tags:** Pulsante TAG non sempre cliccabile, troppo in fondo.
*   [ ] **Orizzontale:** Verificare visualizzazione schermo orizzontale (logo mangiato, cover gigante).

#### Problemi Strutturali / Backend
*   [ ] **Menu Admin:** Ogni tot crasha.

#### Varie
*   [ ] **Home:** Inserire "curated by -nome-" (attendere layout).
*   [ ] **Home:** Escludi post (valutare se rimuovere).
*   [ ] **Pulsante +:** Valutare se tenere.
*   [ ] **Progetti:** Nome delle foto on hover (come Music Archives).
*   [ ] **About:** Scrivere breve guida su modifica pagina ABOUT.
*   [ ] **About Press:** Aggiungere in "selected press" 2025: BeTalkZ PODCAST (YouTube + Spotify) e Visioni (RaiPlay).
*   [ ] **Tags:** Manca pulsante back e progettazione landing tag.
