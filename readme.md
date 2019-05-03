# Sekiro

Trasforma una pagina del sito in un'area riservata. Ogni singola risorsa aggiunta all'area riservata (file, immagine o video) può essere resa visibile a un singolo utente o a una lista di utenti "subscriber". Solo gli utenti approvati dall'amministratore possono accedere all'area riservata. Richiede il plugin <a href="https://pods.io">Pods</a>.

## Proteggere la cartella uploads

Per impedire l'accesso diretto ai file dell'area riservata basta creare un file `.htaccess` nella cartella `/wp-content/uploads` con questo contenuto:

```
RewriteEngine On

RewriteCond %{REQUEST_URI} !^.*doc_allowed.pdf [NC] 

RewriteCond %{HTTP_COOKIE} !^.*wordpress_logged_in.*$ [NC]

RewriteRule .*\.(doc|xls|pdf|xlsx|docx|zip)$ https://%{SERVER_NAME}/ [NC]
```

Se non è presente un cookie il cui nome contiene la stringa *wordpress_logged_in* (quindi l'utente non è loggato) o il file richiesto non un file che contiene la stringa *doc_allowed.pdf* (l'unico file che si consente di scaricare senza essere loggati) qualsiasi richiesta di file doc, xls ecc... viene rediretta alla root.





