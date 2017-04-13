<?php

namespace Service;

class GallicaDownloaderService
{
    private $repertoireDownload = 'downloads';
	private $repertoireVignettes = 'vignettes' ;
	private $repertoireImages = 'images';
	// http://gallica.bnf.fr/iiif/ark:/12148/btv1b10721147q/f52/full/full/0/native.jpg
	private $baseUrl = "http://gallica.bnf.fr/iiif/ark:/12148" ;
    
    public function __construct($config)
    {
        $this->baseUrl = $config['gallicaBaseUrl'];
        $this->repertoireDownload = $config['projectsPath'];
    }

	public function info($identifier)
	{
        // Récupération du manifest.info sur le serveur Gallica
        // Ex : http://gallica.bnf.fr/iiif/ark:/12148/bpt6k5420939g/manifest.json
		$infoUrl = sprintf("%s/%s/manifest.json", $this->baseUrl, $identifier);
		$jsonInfo = @file_get_contents($infoUrl);
        
        // Check réponse
        if ($jsonInfo === false) {
            return ['error' => "Gallica n'est pas accessible. Veuillez vérifier votre connexion (les proxies ne sont pas pris en compte)."] ;
        }
        
		$info = json_decode($jsonInfo);
        
        // S'il n'y a pas de champ séquence, alors identifiant faux ou ressource non trouvée
        if (!isset($info->sequences)) {
            return ['error' => "Identifiant erronné ou ressource non trouvée."] ;
        }
        
        // Init du répertoire des téléchargements s'il n'existe pas encore
        if (!file_exists($this->repertoireDownload)) {
			@mkdir($this->repertoireDownload, 0777);
		}
        // Création des répertoires de la ressource
		$directory = $this->repertoireDownload . '/' .$identifier ;
		$fullDirectory = $directory ;
		if (!file_exists($fullDirectory)) {
			@mkdir($fullDirectory, 0777);
		}
		if (!file_exists($fullDirectory.'/'.$this->repertoireVignettes)) {
			@mkdir($fullDirectory.'/'.$this->repertoireVignettes, 0777);
		}
		if (!file_exists($fullDirectory.'/'.$this->repertoireImages)) {
			@mkdir($fullDirectory.'/'.$this->repertoireImages, 0777);
		}
        // Check création des répertoires
        if (!file_exists($fullDirectory.'/'.$this->repertoireImages)) {
            return ['error' => "Impossible d'écrire dans le répertoire des projets."];
        }
        
        $images = [] ;
        $minW = 100000 ;
        $maxW = 0 ;
        $minH = 100000 ;
        $maxH = 0 ;
        $sumW = 0;
        $sumH = 0;
        // Création du tableau des pages, avec min et max, et moyenne
        foreach ($info->sequences[0]->canvases as $canvas) {
            $image = $canvas->images[0]->resource ;
            if ($minW > $image->width) { $minW = $image->width;}
            if ($maxW < $image->width) { $maxW = $image->width;}
            if ($minH > $image->height) { $minH = $image->height;}
            if ($maxH < $image->height) { $maxH = $image->height;}
            $sumW += $image->width;
            $sumH += $image->height;
            preg_match('@/f(\d+)$@', $image->service->{'@id'}, $matches);
            $page = (int) $matches[1];
            $images[$page] = [
                'url' => $image->service->{'@id'},
                'width' => $image->width,
                'height' => $image->height,
                'page' => $page
            ];
        }
        $meanW = round($sumW / count($images));
        $meanH = round($sumH / count($images));
        
        // Calcul écart et écart moyen
        $sumEcartW = 0 ;
        $sumEcartH = 0 ;
        foreach($images as &$image){
            $ecartW = abs ($image['width'] - $meanW);
            $ecartH = abs ($image['height'] - $meanH);
            $image['ecartW'] = $ecartW ;
            $image['ecartH'] = $ecartH ;
            $sumEcartH += $ecartH;
            $sumEcartW += $ecartW;
        }
        $meanEcartW = round($sumEcartW / count($images));
        $meanEcartH = round($sumEcartH / count($images));
        
        // Tableau des metadatas
        $metadata = [];
        foreach ($info->metadata as $singleData){
            $metadata[$singleData->label] = $singleData->value;
        }
        // Préparation de la réponse
		$resource = array(
            'id' => $identifier,
			'name' => $info->label,
            'title' => isset($metadata['Title']) ? $metadata['Title']: 'Inconnu',
            'date' => isset($metadata['Date']) ? $metadata['Date']: 'Inconnue',
            'provider' => isset($metadata['Provider']) ? $metadata['Provider']: 'Inconnue',
            'source' => isset($metadata['Shelfmark']) ? $metadata['Shelfmark']: 'Inconnue',
            'author' => isset($metadata['Creator']) ? $metadata['Creator']: 'Inconnu',
			'description' => $info->description,
            'url' => $info->related,
            'todo' => $images,
            'w' => [
                'min' => $minW,
                'max' => $maxW,
                'mean' => $meanW,
                'ecart' => $meanEcartW,
            ],
            'h' => [
                'min' => $minH,
                'max' => $maxH,
                'mean' => $meanH,
                'ecart' => $meanEcartH,
            ],
            'nbVues' => count($images),
            
		);
        
        
        // Ecriture du fichier info
		$fileInfo = fopen($directory.'/'. $identifier.'.txt', 'w');
		fwrite($fileInfo, $resource['title'] ."\r\n");
		fwrite($fileInfo, "--------------------------\r\n");
        fwrite($fileInfo, "Identifiant : {$resource['id']}\r\n");
		fwrite($fileInfo, "Auteur      : {$resource['author']}\r\n");
		fwrite($fileInfo, "Date        : {$resource['date']}\r\n");
        fwrite($fileInfo, "Nom         : {$resource['name']}\r\n");
        fwrite($fileInfo, "Provenance  : {$resource['provider']}\r\n");
		fwrite($fileInfo, "Source      : {$resource['source']}\r\n");
        fwrite($fileInfo, "Description : {$resource['description']}\r\n");
        fwrite($fileInfo, "URL         : {$resource['url']}\r\n");
        fwrite($fileInfo, "Nb de vues  : ". count($resource['todo'])."\r\n");
        
		fclose($fileInfo);

		return $resource;
	}

	public function download($image, $source, $destination, $options) {
		$page         = $image['page'];
		$resourceId   = $options['id'];
		$offsetX      = $source['x'];
		$offsetY      = $source['y'];
		$width        = $source['w'];
		$height       = $source['h'];
		$maxWidth     = $destination['maxW'];
		$maxHeight    = $destination['maxH'];
        $suffixe      = $options['suffixe'];

		$region = "full";
		$size = "full"; // max not supported
		$rotation = "0" ;
		$quality = "native"; // native, color, grey, bitonal, default
		$format = "jpg"; // jpg, tif, png, gif, jp2, pdf, webp

		if ($width > 0 && $height > 0){
			$region = sprintf("%s,%s,%s,%s", $offsetX, $offsetY, $width, $height);
		}

		if($maxWidth > 0 && $maxHeight>0) {
			$size = sprintf("!%s,%s", $maxWidth, $maxHeight);
		}

		// Here the code to generate imageURL
		// http://gallica.bnf.fr/iiif/ark:/12148/btv1b10500687r/f52/full/full/0/native.jpg
		$imageURL = "http://gallica.bnf.fr/iiif/ark:/12148/$resourceId/f$page/$region/$size/$rotation/$quality.$format";

		$result = $this->downloadImage( $imageURL, $resourceId, $page, $suffixe);
		if (isset($result['error'])) {
			return [
				'result'    => 'error',
				'message' => $result['error'],
			];
		}

		return [
			'result'    => 'success',
			'filename' => $result['filename'],
		];
	}


	private function downloadImage($imageUrl, $resourceId, $pageNumber, $suffixe)
	{
		$directory = $this->repertoireDownload . '/'.$resourceId ;

		$image = file_get_contents($imageUrl);
		if ($image === false) {
			return ['error' => 'Erreur en lisant ' . $imageUrl ];
		}

		// Ecriture de l'image
		$filename = sprintf("%03d%s.jpg", $pageNumber, $suffixe) ;
		$fullFilename = $directory. '/' . $this->repertoireImages. '/' .$filename;
		file_put_contents($fullFilename, $image);

		// Création d'une vignette
		$fullThFilename = $directory. '/' . $this->repertoireVignettes. '/' .$filename;
		$this->createThumbnail($fullFilename, $fullThFilename);

		return [
            'filename' => $filename
        ] ;
	}

	private function createThumbnail($imageUrl, $thumbnailUrl) {
		$thumbWidth = 200 ;
		$img = imagecreatefromjpeg( $imageUrl );
		$width = imagesx( $img );
		$height = imagesy( $img );

		// calculate thumbnail size
		$new_width = $thumbWidth;
		$new_height = floor( $height * ( $thumbWidth / $width ) );

		// create a new temporary image
		$tmp_img = imagecreatetruecolor( $new_width, $new_height );

		// copy and resize old image into new image
		imagecopyresized( $tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height );

		// save thumbnail into a file
		imagejpeg( $tmp_img, $thumbnailUrl, 90 );
	}
}