# Sekiro

Trasforma una pagina del sito in un'area riservata. Ogni singola risorsa aggiunta all'area riservata (file, immagine o video) può essere resa visibile a un singolo utente o a una lista di utenti "subscriber". Solo gli utenti approvati dall'amministratore possono accedere all'area riservata. Richiede il plugin <a href="https://pods.io">Pods</a>.

## Proteggere la cartella uploads

Per impedire l'accesso diretto ai file dell'area riservata basta creare un file `.htaccess` nella cartella `/wp-content/uploads/` con questo contenuto:

```RewriteEngine On
RewriteCond %{HTTP_REFERER} !^http://%{SERVER_NAME}/ [NC]
RewriteCond %{REQUEST_URI} !hotlink\.(gif|png|jpg|doc|xls|pdf|html|htm|xlsx|docx|mp4|mov) [NC]
RewriteCond %{HTTP_COOKIE} !^.*wordpress_logged_in.*$ [NC]
RewriteRule .*\.(gif|png|jpg|doc|xls|pdf|html|htm|xlsx|docx|mp4|mov)$ http://%{SERVER_NAME}/ [NC]
```





