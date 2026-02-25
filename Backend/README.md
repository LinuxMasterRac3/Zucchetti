# Z-Volta Asset Management

## Descrizione Progetto
L’azienda **Z-Volta** opera nel settore terziario e necessita di una soluzione software per la gestione della nuova sede in modalità "smartworking". Il sistema permette la gestione di asset aziendali (scrivanie, sale meeting, parcheggi) e la loro prenotazione da parte del personale.

### Profili Utente
*   **Gestore (Admin):** Gestione completa di utenti e asset, visibilità totale.
*   **Coordinatore:** Prenota per sé (fino a 3 asset) e visualizza le prenotazioni del proprio team.
*   **Dipendente:** Prenota asset (1 max) di tipo scrivania o sala riunioni.

### Asset Gestiti
1.  **Tipo A:** Scrivania base (Scrivania + Cassettiera + Armadietto)
2.  **Tipo A2:** Scrivania attrezzata (+ Monitor)
3.  **Tipo B:** Sala Riunioni
4.  **Tipo C:** Posto Auto

## Diagramma ER (Entity-Relationship)

Il seguente diagramma illustra la struttura del database progettato nel file `database.sql`.

```mermaid
erDiagram
    RUOLI {
        int id PK
        string nome "Unique"
        string descrizione
        int max_prenotazioni "Limit (1, 3, etc.)"
    }

    UTENTI {
        int id PK
        string nome
        string cognome
        string username "Unique"
        string password "Plaintext (No Encryption Req)"
        int id_ruolo FK
        int id_coordinatore FK "Nullable (Self-ref)"
    }

    TIPI_ASSET {
        int id PK
        string codice_tipo "Unique (A, A2, B, C)"
        string descrizione
    }

    ASSETS {
        int id PK
        string codice_univoco "Unique"
        int id_tipo FK
        enum stato_attuale "disponibile, occupato, non_prenotabile"
    }

    PRENOTAZIONI {
        int id PK
        int id_utente FK
        int id_asset FK
        date data_prenotazione
        time ora_inizio
        time ora_fine
        timestamp timestamp_creazione
        int modifiche_counter "Max 2"
        enum stato_prenotazione "attiva, cancellata, revocata"
    }

    %% Relazioni
    RUOLI ||--|{ UTENTI : "definisce il ruolo di"
    UTENTI ||--o{ UTENTI : "coordina (id_coordinatore)"
    TIPI_ASSET ||--|{ ASSETS : "classifica"
    UTENTI ||--o{ PRENOTAZIONI : "effettua"
    ASSETS ||--o{ PRENOTAZIONI : "oggetto di"
```

## Note Tecniche
*   **Sicurezza Password:** Come da requisiti, la password è salvata senza crittografia (o hash semplice), ma l'applicazione implementa controlli di complessità (8 caratteri, mixed case, numeri, simboli).
*   **Captcha:** L'autenticazione prevede un Captcha generato algoritmicamente.
*   **Mappe:** Gli asset sono identificati da un `codice_univoco` che corrisponde alla loro posizione sulla mappa visuale nel frontend.
