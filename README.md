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

## LISTA CONTROLLO E TEST (CHECKLIST)
Da verificare per ogni nuovo inserimento o modifica ai progetti:

**Layout 3 Colonne:**
*   [ ] Immagine principale verticale corretta (Si/No)
*   [ ] Spazio dal testo presente (Si/No)
*   [ ] Read More corretto e funzionante (Si/No)
*   [ ] Spaziature con spunta amministratore corrette (Si/No)
*   [ ] Immagine al click va a singola (Si/No)
*   [ ] Galleria si chiude bene (Si/No)
*   [ ] Mobile: Layout semplificato 1 colonna (Si/No)

**Layout 1 Colonna:**
*   [ ] Immagine principale corretta (Si/No)
*   [ ] Spazio dal testo presente (Si/No)
*   [ ] Read More corretto e funzionante (Si/No)
*   [ ] Spaziature con punta amministratore corrette (Si/No)
*   [ ] Immagine al click va a singola (Si/No)
*   [ ] Galleria si chiude bene (Si/No)
*   [ ] Mobile: Layout semplificato 1 colonna (Si/No)

## Guida: Gestione Colonne Dinamiche (Pagina About)

Questo sistema permette di impaginare il contenuto della pagina "About" in più colonne dinamicamente, senza dover creare strutture HTML complesse lato editor.

**Scopo del codice:**
Trasformare un unico blocco di testo in più colonne separate ogni volta che viene incontrato il marcatore speciale `[NUOVACOLONNA]`.

### 1. Come funziona (Lato Utente/Editor)
Quando scrivi il testo nella pagina "About" (o dove è presente la classe `.multi-column-content`), puoi forzare l'inizio di una nuova colonna inserendo semplicemente il testo:
`[NUOVACOLONNA]`

**Esempio di input:**
```text
Questo è il testo della prima colonna.
Bla bla bla...
[NUOVACOLONNA]
Questo testo andrà nella seconda colonna.
```

### 2. Analisi Tecnica del Codice

Il codice agisce automaticamente al caricamento della pagina (`DOMContentLoaded`) seguendo questi passaggi:

1.  **Selezione del Contenitore:**
    Cerca l'elemento HTML che ha la classe `.multi-column-content`. Se non lo trova, si ferma.

2.  **Lettura e Divisione:**
    *   Prende tutto il contenuto HTML interno.
    *   Usa la funzione `.split("[NUOVACOLONNA]")` per "tagliare" il testo in vari pezzi ogni volta che trova quel marcatore.

3.  **Reset:**
    Svuota completamente il contenitore originale (`innerHTML = ""`) per prepararlo a ricevere i nuovi blocchi.

4.  **Ricostruzione (Ciclo):**
    Per ogni pezzo di testo tagliato (ogni colonna):
    *   **Pulizia Iniziale:** Rimuove spazi vuoti extra all'inizio e alla fine (`trim()`).
    *   **Creazione Colonna:** Crea un nuovo `div` con classe `column-section`. Questa classe può essere usata nel CSS per dare stile (es. larghezza, float, flex).
    *   **Pulizia Avanzata (Paragrafi Vuoti):** Cerca tutti i tag `<p>` dentro la nuova colonna e rimuove quelli che non contengono testo. Questo evita spazi bianchi indesiderati causati da "a capo" accidentali nell'editor.
    *   **Inserimento:** Aggiunge il nuovo `div` (colonna) al contenitore principale.

### 3. Note per lo Sviluppatore (CSS)
Affinché le colonne si vedano affiancate, dovrai assicurarti che la classe `.multi-column-content` abbia un layout flessibile (es. `display: flex;`) e che `.column-section` abbia le dimensioni corrette.

```css
/* Esempio CSS consigliato */
.multi-column-content {
    display: flex;
    gap: 20px;
}
.column-section {
    flex: 1; /* Le colonne si divideranno lo spazio equamente */
}
```

## Guida: Funzionamento dei Tag

**Come funzionano?**
Il sistema dei tag è **completamente automatico**. Non c'è bisogno di configurare manualmente le pagine di archivio per ogni tag.

**Cosa viene mostrato?**
Cliccando su un tag, il sistema genera automaticamente una pagina che raccoglie **tutti i contenuti** (Progetti, Serie, Portfolio o singole Foto/Allegati) a cui è stato assegnato quel tag specifico.

**Layout e Design:**
La pagina di atterraggio (Landing Page) del tag **rispecchia fedelmente il layout della Homepage**.
*   Usa la stessa griglia dinamica.
*   Non è necessario progettare graficamente ogni pagina tag: il sistema si occupa di impaginare i contenuti automaticamente mantenendo la coerenza stilistica del sito.

## Guida: Shortcodes Liste (Tag & Categorie)

Sono disponibili tre nuovi shortcode per visualizzare liste di tassonomie con un layout a griglia (3 colonne su desktop, 1 colonna su mobile).

### 1. Lista Categorie
Mostra tutte le categorie utilizzate (escluse quelle vuote) con il conteggio dei post.

**Shortcode:** `[iml_categories_list]`

### 2. Lista Tag
Mostra tutti i tag utilizzati (esclusi quelli vuoti) con il conteggio dei post.

**Shortcode:** `[iml_tags_list]`

### 3. Lista Completa (Categorie + Tag)
Mostra prima la lista delle categorie e subito dopo la lista dei tag, mantenendo lo stesso stile.

**Shortcode:** `[iml_taxonomies_list]`

### Note di Stile
Il layout è gestito automaticamente tramite CSS Grid e si adatta alla larghezza dello schermo.
*   **Desktop (>992px):** 3 colonne.
*   **Mobile (<992px):** 1 colonna.

## Changelog & Status

### FATTO (Done)
*   **Migrazione Codice Admin:** Tutto il codice originale (`all_admin_code.php`) è stato migrato in una struttura modulare (`includes/`).
*   **Integrazione Registrazione Campi:** Le registrazioni dei CPT e Meta Box (`registrazionecampi.php`) sono state integrate in `includes/cpt-registrations.php`.
*   **Migrazione Frontend:** 
    *   La logica homepage è ora nello shortcode `[iml_homepage_grid]`.
    *   La logica portfolio single è ora nello shortcode `[iml_portfolio_grid]`.
    *   La logica archivio (tag/cat) è ora nello shortcode `[iml_archive_grid]`.
    *   La logica archivio generico è ora nello shortcode `[iml_generic_archive_grid]`.
    *   La logica serie/progetto single è ora nello shortcode `[iml_project_single]`.
    *   La logica serie/progetto single è ora nello shortcode `[iml_serie_single]`.
    *   La logica attachment single è ora nello shortcode `[iml_attachment_single]`.
*   **Assets Frontend:** CSS e JS sono stati separati in file dedicati (`frontend/style.css`, `frontend/script.js`) e vengono caricati correttamente.
*   **Webhook:** Configurazione e test webhook GitHub completato.
*   **Bug Builder Home:** Risolvere bug builder pagina HOME (spostare builder animazione o soluzione in place, ottimizzazione caricamento).
*   **Builder Dropdown:** Dividere righe dropdown builder in 3 colonne per esperienza piu' friendly.
*   **Ottimizzazione Immagini:** Ottimizzazione Immagini che impediscono il caricamento del builder.
*   **Backend Upload:** Risolto bug upload da Media non funzionante su Portfolio (conflitto ID e inizializzazione array vuoto).
*   **Safari CSS:** Risolto bug foto "cover" progetti esplode su Safari (aggiunto `width: -webkit-fill-available!important`).
*   **Foto Cover Contain:** Applicata regola CSS per fit orizzontale con spazietto di 8px.
*   **Menu Desktop:** Allineare menu titoli con menu tendina (allineare a fine parola, bandiera allineata a fine parola titolo).
*   **Menu Desktop:** Allineare con testi (valutare fattibilità tecnica).
*   **Menu Desktop:** Fix Z-Index e posizionamento colonna destra.
*   **Backend Upload:** Risolto bug upload da Media non funzionante su Portfolio e Progetti (conflitto JS).
*   **Menu Mobile:** Aggiungere una piccola "V" per segnare apertura sottomenu al click.
*   **Testi Progetti:** Implementare "Read More" per testi lunghi (desktop e valutare tablet).
*   **Foto Cover:** Applicare regola: verticale fit 100vh, orizzontale fit container.
*   **Foto Cover Orizzontale:** Gestione specifica nei progetti (es. Jhalak, Termini Underground).
*   **Allineamento Foto:** Allineare foto "cover" orizzontale quando a dx c’è foto orizzontale (es. COEZ).
*   **Menu:** Finisce troppo in basso e diventa non cliccabile.
*   **Bug Scroll:** Pagina singola progetto cambia da sola allo scroll touch.
*   **Menu Admin:** Ogni tot crasha.
*   **Home:** Escludi post (valutare se rimuovere).
*   **Progetti:** Nome delle foto on hover (come Music Archives).
*   **Menu Desktop:** Aggiornato stile per allineamento a destra (flex-end) delle voci di menu.
*   [v] **Home Builder:** Risolvere bug builder pagina HOME (spostare builder animazione in nuova pagina con label apposito).
*   [v] **Home Intro:** Animazione nome in INTRO — (In attesa file da cartella drive).
*   [v] **Home Preloading:** Inserimento animazione preloading pagina.
*   [v] **Fix Dropdown Caricamento:** Risolto problema connessione chiusa nel caricamento immagini builder. Implementato sistema di caricamento concorrente a coda (batch da 30, max 6 connessioni parallele) con barra di progresso e auto-riempimento intelligente. (Si/Done)
*   [v] **Portfolio Builder Update:**
    1.  **Overlay Controlli Griglia:** Aggiunto un overlay semi-trasparente in basso a sinistra su ogni elemento della griglia. Include:
        *   Dropdown Allineamento (Alto/Basso o Sinistra/Destra).
        *   Indicatore "Pagina Singola" (spunta verde) se attivo.
        *   Etichetta del tipo di post.
    2.  **Indicatore Pagina Singola:** Implementato anche per Project e Homepage Builder (visualizzazione stato "Pagina Singola" nella griglia).
    3.  **Salvataggio Allineamento:** Ora l'allineamento degli elementi (es. sinistra/destra per immagini verticali) viene salvato correttamente anche nel meta campo principale `portfolio_items_alignment`, garantendo la persistenza delle scelte e la retrocompatibilità.
    4.  **Live Preview Allineamento:** Aggiunto script JS che aggiorna visivamente la posizione dell'immagine nella griglia non appena si cambia l'opzione nel menu a tendina (es. da sinistra a destra), senza dover ricaricare la pagina.
    5.  **Rilevamento Orientamento:** Affinata la logica per determinare se un'immagine è verticale, assicurando il controllo corretto di larghezza e altezza.
*   [v] **Frontend Grids Fix:**
    1.  **Pulizia Attachment:** Rimosse le chiamate non necessarie a categorie, tag e anno per gli elementi di tipo `attachment` nelle griglie di Homepage, Portfolio e Project (prevenzione errori PHP).
    2.  **Indicatore Frontend:** Aggiunto un indicatore visivo ("❐") nell'overlay delle immagini che hanno una "Pagina Singola" attiva, per segnalare la cliccabilità.
*   [v] **Attachment Single Page:**
    1.  **Lightbox Navigation:** Ripristinata configurazione semplice (`overlay: false`, `docClose: false`) per risolvere problemi di navigazione (frecce che sparivano).
    2.  **Chiusura Custom:** Implementato script JS per chiudere la lightbox cliccando sullo sfondo vuoto (`.sl-wrapper` o `.sl-overlay`), evitando chiusure accidentali durante la navigazione.
    3.  **Supporto Portfolio:** Aggiunta logica PHP per recuperare `portfolio_items` (oltre a `prj_items`) per permettere la navigazione tra allegati anche quando appartengono a un Portfolio padre.
    4.  **Allineamento:** Verificata e confermata l'applicazione della classe di allineamento (Alto/Basso/Sinistra/Destra) al container dell'immagine.
*   [v] **Taxonomy Lists:** Creati shortcode `[iml_categories_list]`, `[iml_tags_list]`, `[iml_taxonomies_list]` per visualizzare griglie responsive di categorie e tag.
*   [v] **Frontend Fixes:**
    *   **Homepage:** Rimossa icona "Pagina Singola" indesiderata dalla griglia home.
    *   **Titoli:** Fix allineamento verticale titoli in griglia (aggiunto `margin-top: auto`).
*   [v] Da rivedere **Foto Cover Contain:** Applicare regola: fit orizzontale foto cover progetto  in left column non si deve sovrapporre al testo.
*   [v] effetto escludi in home – forse levare decidere alla fine (io, Riccardo, lo toglierei)
*   [v] **Checklist e Documentazione:** Creata checklist interna per lo sviluppo (`includes/post-types/attachment/frontend.php`) e aggiornato README con stato avanzamento.
*   [v] **About Press:** Aggiungere in "selected press" 2025:
    *   **BeTalkZ PODCAST:** [Video YouTube](https://www.youtube.com/watch?v=r7okOw60tDw) + [Spotify](https://open.spotify.com/episode/2kdbP47CNeQWjnjFUgthnZ?si=rW7CtrmgRt2PiuQ2SBr)
    *   **Visioni (Rai5):** [RaiPlay](https://www.raiplay.it/video/2025/03/Visioni-Ritratto-di-Donne-Prima-Visione-245ce57b-b094-4982-bb09-56ce3713f9e6.html)

### TODO (Elenco lavorazioni in sospeso)


#### Formattazione / Layout
*   [v] **Cross-browser:** Fare check sui vari browser.

#### Mobile
*   [v] **Progetti:** Testo troppo a filo.
*   [v] **Tags:** Pulsante TAG non sempre cliccabile, troppo in fondo.
*   [v] **Orizzontale:** Verificare visualizzazione schermo orizzontale (logo mangiato, cover gigante).

#### Problemi Strutturali / Backend
*   (Nessuno attivo)

#### Varie
*   [v] **Home:** Inserire "curated by -nome-" (attendere layout).
*   [v] **Pulsante +:** Valutare se tenere.(NO)
*   [v] **About:** Scrivere breve guida su modifica pagina ABOUT. (Fatto)
*   [v] **Tags:** Manca pulsante back e progettazione landing tag.
