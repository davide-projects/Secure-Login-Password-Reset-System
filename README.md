# 🧿 Secure Login & Password Reset System
 
Sistema di autenticazione moderno, modulare e sicuro, sviluppato in **PHP + API REST**, con protezioni anti-bruteforce, validazioni lato client/server e architettura scalabile.
 
---
 
## 🚀 Caratteristiche principali
 
- Login API-based (nessuna logica nel form, tutto centralizzato in `/api/users.php`)
- Registrazione API-based con invio automatico di **email di benvenuto** (PHPMailer + SMTP)
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
    /lib
        /PHPMailer
            PHPMailer.php
            SMTP.php
            Exception.php
 
/javascript
    login.js
    reset_validation.js
 
password_reset.php
password_reset_form.php
register.php
login.php
dashboard.php
index.html
config.php
style.css
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
 
## 📧 Sistema email
 
Il progetto integra **PHPMailer** per l'invio di email transazionali tramite SMTP.
 
### ✔ Email di benvenuto
Inviata automaticamente al completamento della registrazione, contiene:
- Saluto personalizzato con il nickname dell'utente
- Conferma creazione account
- Consiglio sulla sicurezza delle credenziali
### ⚙️ Configurazione SMTP
Le credenziali SMTP sono centralizzate in `getSmtpConfig()` dentro `api/functions.php`.
 
Per lo sviluppo locale è consigliato **Mailpit** (integrato in Laragon):
 
```php
'host'     => 'localhost',
'port'     => 1025,
'secure'   => '',
'username' => '',
'password' => '',
```
 
Per la produzione sostituire con le credenziali di un provider SMTP reale (Mailtrap Live, SendGrid, Brevo, ecc.).
 
---
 
## 🧩 API Endpoints
 
### 🔹 Registrazione
`POST /api/users.php`
 
```json
{
  "nickname": "MikeMazz",
  "nome": "Mike",
  "cognome": "Mazzosky",
  "email": "mike@gmail.com",
  "password": "Password123"
}
```
 
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
 
### ✔ Registrazione (fetch API)
Il form invia i dati come JSON all'API tramite `fetch()`, riceve la risposta e mostra il messaggio senza reload. In caso di successo reindirizza al login dopo 2 secondi.
 
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
| Registrazione con email di benvenuto | ✅ |
 
---
 
## 🛠️ Requisiti
 
- PHP 8+
- MySQL / MariaDB
- Apache (Laragon consigliato)
- Estensione cURL attiva
- PHPMailer (incluso in `/api/lib/PHPMailer`)
---
 
## 📦 Installazione
 
1. Clona la repository
   ```bash
   git clone https://github.com/tuo-utente/tuo-repo.git
   ```
2. Crea il database ed esegui `database.sql`
3. Configura `api/db.php` con le tue credenziali
4. Configura `getSmtpConfig()` in `api/functions.php` con le credenziali SMTP
5. Avvia il server locale
6. Apri nel browser:
   ```
   http://login-register.test/login.php
   ```
 
---
 
## 🧑‍💻 Autore
 
**Davide** — Junior Full-Stack Developer & Workflow Architect
 
---
 
## 🔮 Sviluppi futuri
 
- [ ] Notifica email al reset password completato
- [ ] Token sicuro per il reset password (link con scadenza via email)
- [ ] Integrazione con sito vetrina
- [ ] Dashboard utente avanzata
- [ ] Log attività utente
- [ ] Rate limiting distribuito (Redis)