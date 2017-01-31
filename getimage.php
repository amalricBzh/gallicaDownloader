<?php


// Init

/**
 * Docs : http://iiif.io/api/image/2.1/#image-request-uri-syntax
 * http://doc.biblissima-condorcet.fr/iiif-api-image
 * http://doc.biblissima-condorcet.fr/iiif-api-presentation
 *
 * Ressource	Modèle d'URI
 * Collection	{scheme}://{host}/{prefix}/collection/{name}
 * Manifest	{scheme}://{host}/{prefix}/{identifier}/manifest
 * Sequence	{scheme}://{host}/{prefix}/{identifier}/sequence/{name}
 * Canvas	{scheme}://{host}/{prefix}/{identifier}/canvas/{name}
 * Annotation	{scheme}://{host}/{prefix}/{identifier}/annotation/{name}
 * AnnotationList	{scheme}://{host}/{prefix}/{identifier}/list/{name}
 * Range	{scheme}://{host}/{prefix}/{identifier}/range/{name}
 * Layer	{scheme}://{host}/{prefix}/{identifier}/layer/{name}
 * Content	{scheme}://{host}/{prefix}/{identifier}/res/{name}.{format}
 *
 *
 * http://tympanus.net/codrops/2014/10/30/resizing-cropping-images-canvas/
 * http://talkerscode.com/webtricks/resize-and-crop-image-using-php-and-jquery.php
 *
 *
 * Tester :
 * https://github.com/scottcheng/cropit
 */



class GallicaDownloader
{
	private $repertoireVignettes = 'vignettes' ;
	private $repertoireImages = 'images';
	// http://gallica.bnf.fr/iiif/ark:/12148/btv1b10721147q/f52/full/full/0/native.jpg
	private $baseUrl = "http://gallica.bnf.fr/iiif/ark:/12148" ;

	public function infoAction()
	{
		$identifier		= $_POST['id'] ;
		$infoUrl = sprintf("%s/%s/manifest.json",
			$this->baseUrl, $identifier);

		$jsonInfo = @file_get_contents($infoUrl);

		$info = json_decode($jsonInfo);
		$name = iconv("UTF-8", "Windows-1252", $info->label) ;

		$directory = "downloads/$name" ;
		$fullDirectory = getcwd().DIRECTORY_SEPARATOR.$directory ;

		if (!file_exists($fullDirectory)) {
			@mkdir($fullDirectory, 0777);
		}
		if (!file_exists($fullDirectory.DIRECTORY_SEPARATOR.$this->repertoireVignettes)) {
			@mkdir($fullDirectory.DIRECTORY_SEPARATOR.$this->repertoireVignettes, 0777);
		}
		if (!file_exists($fullDirectory.DIRECTORY_SEPARATOR.$this->repertoireImages)) {
			@mkdir($fullDirectory.DIRECTORY_SEPARATOR.$this->repertoireImages, 0777);
		}

		$fileInfo = fopen($directory.DIRECTORY_SEPARATOR. $identifier.'.txt', 'w');
		fwrite($fileInfo, $info->label ."\r\n");
		fwrite($fileInfo, "-----------------------\r\n");
		fwrite($fileInfo, "Attribution : $info->attribution\r\n");
		fwrite($fileInfo, "Identifiant : $identifier\r\n");
		fwrite($fileInfo, "Lien  : $info->related\r\n\r\n");
		fwrite($fileInfo, "Description : $info->description\r\n");
		fclose($fileInfo);

		$result = array(
			'result' => 'success',
			'status' => 'success',
			'name' => $info->label,
			'description' => $info->description,
		);

		return json_encode($result);
		//return $jsonInfo;
	}

	public function downloadAction() {
		$page         = $_POST['p'];
		$resourceId   = $_POST['i'];
		$resourceName = $_POST['n'];
		$offsetX      = $_POST['x'];
		$offsetY      = $_POST['y'];
		$width        = $_POST['w'];
		$height       = $_POST['h'];
		$maxWidth     = $_POST['mw'];
		$maxHeight    = $_POST['mh'];

		$region = "full";
		$size = "full"; // max not supported
		$rotation = "0" ;
		$quality = "native"; // native, color, grey, bitonal, default
		$format = "jpg"; // jpg, tif, png, gif, jp2, pdf, webp

		if ($width > 0 && $height > 0){
			$region = sprintf("%s,%s,%s,%s", $offsetX, $offsetY, $width, $height);
		}

		if($maxWidth > 0 && $maxHeight>0) {
			$size = sprintf("!%s,%s", $maxWidth,$maxHeight);
		}

		// Here the code to generate imageURL
		// http://gallica.bnf.fr/iiif/ark:/12148/btv1b10500687r/f52/full/full/0/native.jpg
		$imageURL = "http://gallica.bnf.fr/iiif/ark:/12148/$resourceId/f$page/$region/$size/0/native.jpg";

		$tmp = $this->downloadImage( $imageURL, $resourceName, $page);

		$result = array(
			'result'    => 'success',
			'imagepath' => $tmp['imageSrc'],
			'thumbpath' => $tmp['imageThumb'],
		);

		/*
		if ($page > 35) {
			sendError('Une erreur est survenue pendant la récupération de l\'image (simulation).');
		}
		*/
		return json_encode( $result );
	}


	private function downloadImage($imageUrl, $bookName, $pageNumber)
	{
		$directory = "downloads". DIRECTORY_SEPARATOR.$bookName ;
		$resourceName = iconv("UTF-8", "Windows-1252", $bookName) ;
		$fullDirectory = getcwd().DIRECTORY_SEPARATOR."downloads". DIRECTORY_SEPARATOR.$resourceName ;

		$image = @file_get_contents($imageUrl);
		if ($image === false) {
			sendEnd('Fin du livre atteinte.');
		}

		$filename = sprintf("%03d.jpg", $pageNumber) ;
		$shortFilename = $directory. DIRECTORY_SEPARATOR . $this->repertoireImages. DIRECTORY_SEPARATOR .$filename;
		$shortThFilename = $directory. DIRECTORY_SEPARATOR . $this->repertoireVignettes. DIRECTORY_SEPARATOR .$filename;
		$fullFilename = $fullDirectory. DIRECTORY_SEPARATOR . $this->repertoireImages. DIRECTORY_SEPARATOR .$filename;
		$fullThFilename = $fullDirectory. DIRECTORY_SEPARATOR . $this->repertoireVignettes. DIRECTORY_SEPARATOR .$filename;

		file_put_contents($fullFilename, $image);

		// Création d'une vignette
		$this->createThumbnail($fullFilename, $fullThFilename);

		return array(
			'imageSrc' => $shortFilename,
			'imageThumb' => $shortThFilename,
		) ;
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





function sendError ($message) {
	$result = array(
		'result' => 'error',
		'message' => $message,
		'status' => 'error',
	);
	echo json_encode($result);
	die();
}

function sendEnd ($message) {
	$result = array(
		'result' => 'error',
		'message' => $message,
		'status' => 'end',
	);
	echo json_encode($result);
	die();
}


/** Start */

// Checks if it's post
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_GET['debug'] != '1') {
	echo 'Your request is invalid.' ;
	die();
}

$action         = $_POST['action'];
$downloader = new GallicaDownloader();

/** Router */
if ($action == 'info') {
	echo $downloader->infoAction();
} elseif ($action == 'download') {
	echo $downloader->downloadAction();
}