# 🧿 Secure Login & Password Reset System
 
Sistema di autenticazione moderno, modulare e sicuro, sviluppato in **PHP + API REST**, con protezioni anti-bruteforce, validazioni lato client/server e architettura scalabile.
 
---
 
## 🚀 Caratteristiche principali
 
- Login API-based (nessuna logica nel form, tutto centralizzato in `/api/users.php`)
- Reset password API-based con pagina di esito dedicata
- **Protezione anti-bruteforce avanzata**
  - Blocco email dopo X tentativi falliti
  - Blocco IP dopo Y tentativi falliti
  - Logging tentativi in database
- **Validazioni lato client** (JavaScript)
  - Email valida
  - Password minima
  - Password coincidenti
- **Validazioni lato server** (PHP)
  - Sanitizzazione input
  - Controlli di sicurezza
  - Messaggi coerenti e strutturati
- Architettura modulare e scalabile
- Compatibile con **SonarQube** (bassa complessità, nessun codice duplicato)
- Pronto per integrazione con sito vetrina
---
 
## 📁 Struttura del progetto
 
```
/api
    db.php
    functions.php
    users.php
 
/css
    style.css
 
/javascript
    login.js
    reset_validation.js
 
/views
    login.php
    register.php
    password_reset_form.php
    dashboard.php
 
password_reset.php
index.php
README.md
```
 
---
 
## 🔐 Sicurezza integrata
 
### ✔ Blocco Email
Dopo **5 tentativi falliti** sulla stessa email:
```
Troppi tentativi falliti su questo account. Attendi qualche minuto.
```
 
### ✔ Blocco IP
Dopo **10 tentativi falliti** dallo stesso indirizzo IP:
```
Troppi tentativi da questo indirizzo IP. Attendi qualche minuto.
```
 
### ✔ Logging tentativi
Ogni tentativo viene registrato nella tabella `login_attempts` con:
 
| Campo | Descrizione |
|---|---|
| `email` | Email utilizzata nel tentativo |
| `ip` | Indirizzo IP del client |
| `attempt_time` | Timestamp del tentativo |
| `success` | Esito (0 = fallito, 1 = riuscito) |
 
---
 
## 🧩 API Endpoints
 
### 🔹 Login
`POST /api/users.php`
 
```json
{
  "action": "login",
  "email": "utente@example.com",
  "password": "Password123"
}
```
 
### 🔹 Reset Password
`POST /api/users.php`
 
```json
{
  "action": "reset_password",
  "email": "utente@example.com",
  "password": "NuovaPassword123",
  "confirm_password": "NuovaPassword123"
}
```
 
---
 
## 🖥️ Frontend
 
### ✔ Login (fetch API)
Il form non invia dati via POST tradizionale: usa `fetch()` per chiamare l'API e mostrare i messaggi in pagina senza reload.
 
### ✔ Reset Password (cURL → API)
Il form invia i dati a `password_reset.php`, che:
- chiama l'API via cURL
- interpreta la risposta JSON
- mostra una pagina di esito dedicata
### ✔ Validazione JS
`reset_validation.js` gestisce lato client:
- formato email valido
- lunghezza minima password
- corrispondenza password e conferma
---
 
## 🧪 Test eseguiti
 
| Scenario | Esito |
|---|---|
| Login corretto | ✅ |
| Login con email errata | ✅ |
| Login con password errata | ✅ |
| Blocco email | ✅ |
| Blocco IP | ✅ |
| Reset password con errori | ✅ |
| Reset password corretto | ✅ |
| Redirect e messaggi | ✅ |
 
---
 
## 🛠️ Requisiti
 
- PHP 8+
- MySQL / MariaDB
- Apache (Laragon consigliato)
- Estensione cURL attiva
---
 
## 📦 Installazione
 
1. Clona la repository
   ```bash
   git clone https://github.com/tuo-utente/tuo-repo.git
   ```
2. Importa il database
3. Configura `api/db.php` con le tue credenziali
4. Avvia il server locale
5. Apri nel browser:
   ```
   http://login-register.test/login.php
   ```
 
---
 
## 🧑‍💻 Autore
 
**Davide** — Junior Full-Stack Developer & Workflow Architect  
Appassionato di sicurezza, architetture modulari e clean code.
 
---
 
## 🔮 Sviluppi futuri
 
- [ ] Integrazione con sito vetrina
- [ ] Dashboard utente avanzata
- [ ] Log attività utente
- [ ] Notifiche email (SMTP)
- [ ] Token reset password
- [ ] Rate limiting distribuito (Redis)