# Z-Volta Asset Management

Sistema di gestione degli asset (scrivanie, sale riunioni, posti auto) con mappa interattiva 2D, calendario e sistema di prenotazioni. Il progetto è composto da un Backend in PHP (API REST) e un Frontend sviluppato in Vue.js 3 (con Vite e Tailwind CSS).

---

## 🚀 Requisiti di Sistema (Windows)

Per avviare il progetto su una macchina Windows "vergine", avrai bisogno dei seguenti software:

1. **XAMPP**: Pacchetto che include il server web Apache, il database MySQL e phpMyAdmin (per gestire il database con interfaccia grafica).
   - [Scarica XAMPP per Windows](https://www.apachefriends.org/it/index.html)
2. **Node.js**: L'ambiente runtime necessario per installare le librerie del Frontend e avviare il server di sviluppo di Vue.js (include il package manager `npm`).
   - [Scarica Node.js](https://nodejs.org/it/) (Si consiglia la versione LTS)

---

## 🛠️ Guida all'Installazione (Passo a Passo)

### 1. Preparazione del Progetto
Puoi usare **XAMPP** oppure **EasyPHP**. Scegli l'ambiente che preferisci.

**Opzione A: Usando XAMPP**
1. Scarica e installa **XAMPP**.
2. Copia l'intera cartella del progetto (`Zucchetti`) e incollala all'interno della cartella `htdocs` di XAMPP (solitamente `C:\xampp\htdocs\Zucchetti`).

**Opzione B: Usando EasyPHP**
*Cosa cambia con EasyPHP?*
EasyPHP (Devserver) è un'alternativa più leggera e modulare a XAMPP che offre la stessa "triade" (Apache, MySQL, PHPMyAdmin). La differenza principale è la **cartella in cui inserire i file** e il modo in cui avvii il database MySQL.
1. Scarica e installa **EasyPHP Devserver**.
2. Copia l'intera cartella del progetto (`Zucchetti`) e incollala all'interno della cartella `eds-www` di EasyPHP (solitamente `C:\Program Files (x86)\EasyPHP-Devserver-XX\eds-www\Zucchetti`).

### 1.5 Prerequisito Frontend
1. Scarica e installa **Node.js**. Lascia tutte le impostazioni predefinite durante l'installazione.

### 2. Configurazione del Database (MySQL & phpMyAdmin)
**Se usi XAMPP:**
1. Apri lo **XAMPP Control Panel** e avvia i moduli **Apache** e **MySQL**.
**Se usi EasyPHP:**
1. Avvia l'interfaccia web di EasyPHP. Assicurati di avviare l'**HTTP Server** (Apache) e il **Database Server** (MySQL).
2. Clicca sul modulo "Database" > "Open" (che aprirà PHPMyAdmin).

**Passaggi comuni (Creazione DB):**
3. Nel menu a sinistra di phpMyAdmin, seleziona **Nuovo** (o New).
4. Inserisci come nome del database `z_volta_db` e clicca su **Crea**.
5. Seleziona il database appena creato (`z_volta_db`).
6. In alto, clicca sulla scheda **Importa**.
7. Clicca sul pulsante **Scegli file...** e seleziona il file `database.sql` che si trova nella cartella `Backend` del tuo progetto (es. `C:\xampp\htdocs\Zucchetti\Backend\database.sql` oppure il percorso `eds-www`).
8. Scorri in basso e clicca su **Esegui**.

### 3. Configurazione del Backend (PHP API)
1. Modifica, se necessario, il file di configurazione del database. Apri il file `C:\xampp\htdocs\Zucchetti\API\config.php` con un editor di testo (es. Blocco Note, VS Code).
2. Assicurati che le credenziali corrispondano a quelle di default di XAMPP (solitamente sono già corrette):
   ```php
   $servername = "localhost";
   $username = "root";
   $password = ""; // Nessuna password di default su XAMPP locale
   $dbname = "z_volta_db";
   ```

### 4. Configurazione e Avvio del Frontend (Vue.js)
Il Frontend ha bisogno di scaricare i pacchetti `.js` la prima volta che viene utilizzato e necessita di un server di sviluppo per essere visualizzato.
1. Apri il Prompt dei Comandi (Cerca `cmd` su Windows) o PowerShell.
2. Naviga fino alla cartella `Frontend` del progetto scrivendo il comando (adatta il comando in base a se usi XAMPP o EasyPHP):
   ```bash
   cd C:\xampp\htdocs\Zucchetti\Frontend
   ```
   *(Esempio EasyPHP: `cd C:\"Program Files (x86)"\EasyPHP-Devserver-17\eds-www\Zucchetti\Frontend`)*
3. Installa le dipendenze scaricando i pacchetti necessari (questo comando va eseguito **solo la prima volta**):
   ```bash
   npm install
   ```
4. Avvia il server di sviluppo del frontend:
   ```bash
   npm run dev
   ```
5. Il terminale ti restituirà un indirizzo (solitamente `http://localhost:5173/`). Non chiudere questa finestra del terminale finché stai usando l'applicazione.

---

## 💻 Accesso all'Applicazione

Ora che tutto è in esecuzione, puoi accedere al gestionale:

1. Apri il browser e visita l'indirizzo del Frontend:
   👉 **http://localhost:5173/**
2. Effettua il login. Di default, durante l'importazione del database, è stato creato un account amministratore di prova:
   - **Username**: `admin`
   - **Password**: `password123` *(oppure la password definita dal tuo sistema, verifica nella tabella `utenti` su phpMyAdmin)*
3. Risolvi il Captcha dinamico visivo al momento del login.

Benvenuto su Z-Volta Asset Management! Ora puoi usare la Mappa Sede interattiva per prenotare le 50 scrivanie (Piano 1) e consultare le disponibilità delle 5 Sale Riunioni (Piano Terra).
