# gallicaDownloader

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/6d77ba44c3da474a935e160b0277db1d)](https://www.codacy.com/app/amalricBzh/gallicaDownloader?utm_source=github.com&utm_medium=referral&utm_content=amalricBzh/gallicaDownloader&utm_campaign=badger)

Download a book from Gallica (IIIF API)

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

**C:\Windows\System32\drivers\etc** (si vous êtes sous windauze)

    127.0.0.1         gd.localhost
    
## Amélioration et fonctionnalités à implémenter
 - Envoyer les ficheirs téléchargés sur Google Drive
 
