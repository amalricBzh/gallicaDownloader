# gallicaDownloader

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/6d77ba44c3da474a935e160b0277db1d)](https://www.codacy.com/app/amalricBzh/gallicaDownloader?utm_source=github.com&utm_medium=referral&utm_content=amalricBzh/gallicaDownloader&utm_campaign=badger)

Télécharge un livre depuis Gallica (API IIIF) et l'envoie sur votre Google Drive.

## Installation

    git clone https://github.com/amalricBzh/gallicaDownloader
    cd gallicaDownloader
    composer update
    

**httpd-vhosts.conf**

Remplacez <PATH> par le chemin sur votre disque

    <VirtualHost *:80>
        ServerName gd.localhost
        DocumentRoot "<PATH>\gallicaDownloader"
        <Directory "<PATH>\gallicaDownloader">
            DirectoryIndex index.php
            AllowOverride All
            Require all granted
        </Directory>
    </VirtualHost>
    
Pour l'envoi sur Google Drive, si vous utilisez un sous domaine de localhost, il faudra aussi ajouter le locahost car l'authentification Google refuse les sous domaines de localhost.

**C:\Windows\System32\drivers\etc** (si vous êtes sous windauze)

    127.0.0.1         gd.localhost
    
**Certificat cacert.pem**

Allez ici : https://curl.haxx.se/ca/cacert.pem et copiez le contenu du fichier sur votre disque (ex : C:\xampp\cacert.pem)
Editez votre php.ini et ajoutez ou modifiez la ligne suivante :
`curl.cainfo = "[cheminVersLeFichier]\cacert.pem"`

**Google oAuth**

Il vous faudra une clef pour l'API Google (Suivez l'étape 1 de la page https://developers.google.com/drive/v3/web/quickstart/php) que vous mettrez dans `/data/auth/client_id.json` (et non `client_secret.json`, mais c'est configurable dans `src/config.php`).

    
## Amélioration et fonctionnalités à implémenter
 - Voir les [tickets d'amélioration](https://github.com/amalricBzh/gallicaDownloader/issues?q=is%3Aopen+is%3Aissue+label%3Aamélioration)
 - Ajoutez les votres !
 
 
