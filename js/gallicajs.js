
var timeStart = new Date().getTime();
var timeIdle = 0 ;
var timeDownload = 0 ;
var timeDownloadStart = 0 ;
var CFG_StartPage = 1 ;
var CFG_LastPage = 2000 ;
var CFG_CurrentPage = 1 ;
var CFG_NbImagesPerRow = 6 ;
var CFG_Identifiant = 'bpt6k111140c' ;
var CFG_Continue = true ;
var CFG_Restart = 0 ;
var CFG_InProgress = false ;
var CFG_Stopping = false ;
var CFG_Nom = 'Sans nom';

var CFG_X = 0 ;
var CFG_Y = 0 ;
var CFG_Width = 0 ;
var CFG_Height = 0 ;
var CFG_MaxWidth = 3500 ;
var CFG_MaxHeight = 4000 ;
var CFG_Timeout_tries = 3 ;
var CFG_Timeout_occured = 0 ;

// http://gallica.bnf.fr/proxy?method=R&ark=btv1b9061245j.f1&l=3&r=0,2048,256,256
// http://gallica.bnf.fr/ark:/12148/btv1b9061245j/f0.highres
// http://gallica.bnf.fr/iiif/ark:/12148/btv1b10500687r/f52/full/full/0/native.jpg


function getInfoAjax(resource) {
	$.ajax({
		url: "getimage.php",
		type: "POST",
		data: {
			id : CFG_Identifiant,
			action : 'info'
		},
		dataType: "json",
		success : function(data) {
			if (data.result != 'success') {
				if (data.status == 'error') {
					alert ("Erreur : "+data.message+"\r\nLe traitement est interrompu.");
				} else {
					$("div#temporary-messages").append("<br />" + data.message);
				}
				return ;
			}
			CFG_Nom = data.name;
			$(".jumbotron h3 span").html(': ' + data.name);
			$(".jumbotron p").html(data.description);
			$("#name").val(data.name);
			$("#formulaire").hide(500);
			$("#formulaire-avance").show(500);

		},
		error : function(data, status ) {
			console.log ("Error, data : ", data);
			console.log ("       status : ", status);
			alert ("Error requesting infos");
		}
	});

}

function getHeures(toto) {
	toto = toto / 3600;
	if (toto < 1) {
		return '' ;
	} else {
		toto =  toto - toto % 1 ;
		if (toto > 1) { return toto + " heures "; }
		else  { return toto + " heure "; }
	}
}

function getMinutes(toto) {
	toto = toto / 60;
	if (toto < 1) {
		return '' ;
	} else {
		toto =  toto - toto % 1 ;
		toto = toto % 60 ;
		if (toto < 1) { return '' ;}
		if (toto > 1) { return toto + " minutes "; }
		else  { return toto + " minute "; }
	}
}

function getSecondes(toto) {
	return toto % 60 + " secondes ";
}

function downloadPageAjax(page) {
	if (page > CFG_LastPage ){
		CFG_InProgress = false ;
		$("div#temporary-messages").append(' Dernière page atteinte.') ;
		return ;
	}
	CFG_InProgress = true ;
	CFG_CurrentPage = page ;
	
	var width = $("div#resultats div#images").width() / CFG_NbImagesPerRow - 2 ;

	timeDownloadStart = new Date().getTime();
	$.ajax({
		url: "getimage.php",
		type: "POST",
		data: {
			p : page,
			i : CFG_Identifiant,
			n : CFG_Nom,
			x : CFG_X,
			y : CFG_Y,
			w : CFG_Width,
			h : CFG_Height,
			mw: CFG_MaxWidth,
			mh: CFG_MaxHeight,
			action: 'download'
		},
		dataType: "json",
		success : function(data) {
			console.log("Data : ", data);
			if (CFG_Continue) {
				if (data.result != 'success') {
					if (data.status == 'error') {
						alert ("Erreur : "+data.message+"\r\nLe traitement est interrompu.");
					} else {
						$("div#temporary-messages").append("<br />" + data.message);
					}
					CFG_InProgress = false ;
					return ;
				}
				
				var timeCurrent = new Date().getTime();
				timeDownload = timeDownload +timeCurrent - timeDownloadStart ;
				
				var imgUrl = data.imagepath + '?dt=' + timeCurrent;
				var thumbUrl = data.thumbpath + '?dt=' + timeCurrent;
				// On insère l'image sur la page
				var attributs = ' title="Image no ' + page + '" alt="Image no ' + page + '" ' ;
				var datas = ' data-page="' + page + '" data-img="' + imgUrl + '" ' ;
				var htmlCode = '<img src="' + thumbUrl + '" width="' + width +'"' + datas + attributs + '/>' ;
				$("div#resultats div#images").append(htmlCode);
				
				var elapsedTime = Math.floor((timeCurrent - timeStart) / 1000) ;
				var downloadTime = Math.floor(timeDownload / 10) /100 ;
				htmlCode = "Temps total : " + getHeures(elapsedTime)+ getMinutes(elapsedTime) + getSecondes(elapsedTime) ;
				htmlCode = htmlCode + "(dont " + getHeures(downloadTime) + getMinutes(downloadTime) + getSecondes(downloadTime) + " de téléchargement)." ;
				$("div#temporary-messages").html(htmlCode);
				CFG_Timeout_occured = 0 ;
				waitAndDownloadPage(page+1);
			} else {
				console.log("No image : ", data);
				// pas d'image suivante, mais on réinitialise le flag
				CFG_Continue = true ;
				//$("div#temporary-messages").append(" Téléchargement arrêté.");
				// Si on doit redémarrer
				if (CFG_Restart != 0) {
					page = parseInt(CFG_Restart) ;
					waitAndDownloadPage(page);
					CFG_Restart = 0 ;
					CFG_Stopping = false ;
				} else {
					CFG_InProgress = false ;
					CFG_Stopping = false ;
					// On met le bouton de contrôle à Continue
					$("input#control-button").val('Reprendre');
					$("input#control-button").prop('disabled',false);
				}
			}
		},
		fail : function(data) {
			alert( "Error while requesting page." );
			CFG_InProgress = false ;
		},
		error : function(data, status, errorThrown ) {
			if (data.responseText.search('Maximum execution time') > 0) {
				// Time out. On recommence.
				CFG_Timeout_occured = CFG_Timeout_occured + 1 ;
				if (CFG_Timeout_occured >= CFG_Timeout_tries) {
					alert("Erreur de timeout ("+ CFG_Timeout_tries+" tentatives).");
					return ;
				}
				// On attend 30 secondes et on repart
				waitAndDownloadPage(page, 30000);
			}
//			console.log("Error:", data);
//			console.log("Status:", status);
//			console.log("errorThrown:", errorThrown);
		}
	});

}

function waitAndDownloadPage(page, duration) {
	duration = typeof duration !== 'undefined' ? duration : 0;
	CFG_InProgress = true ;
	var waitTime = duration + 2000 + Math.floor((Math.random() * 3000)) ;
	setTimeout(
		function(){downloadPageAjax(page);},
		waitTime
	);
}


$( document ).ready(function() {
	$("input#info-identifiant").val(CFG_Identifiant);
	$("input#x").val(CFG_X);
	$("input#y").val(CFG_Y);
	$("input#width").val(CFG_Width);
	$("input#height").val(CFG_Height);
	$("input#maxWidth").val(CFG_MaxWidth);
	$("input#maxHeight").val(CFG_MaxHeight);
	console.log("Gallica Downloader 3 is ready.");
	
	// Sur validation du formulaire
	$("input#info-submit").click(function() {
		CFG_Identifiant = $("input#info-identifiant").val() ;
		getInfoAjax(CFG_Identifiant);
	});
	
	// validation du formulaire avancé
	$("#advancedSubmit").click(function() {
		$("#formulaire-avance").hide();
		$("#resultats").show();
		// Récupération des valeurs du formulaire
		CFG_StartPage = parseInt($("input#first-page").val()) ;
		CFG_LastPage = parseInt($("input#last-page").val()) ;
		if ($("input#last-page").val() == 'auto') {
			CFG_LastPage = 2000 ;
		}
		CFG_X = parseInt($("input#x").val()) ;
		CFG_Y = parseInt($("input#y").val()) ;
		CFG_Width = parseInt($("input#width").val()) ;
		CFG_Height = parseInt($("input#height").val()) ;
		CFG_MaxWidth = parseInt($("input#maxWidth").val()) ;
		CFG_MaxHeight = parseInt($("input#maxHeight").val()) ;

		$("#messages").html("Téléchargement de "+ CFG_Identifiant+" en cours...<hr />");
		timeStart = new Date().getTime();

		downloadPageAjax(CFG_StartPage);
	});


	$('#images').contextMenu({
		selector: 'img',
		trigger: 'left',
		callback: function(key, options) {
		   if (key == "view") {
				var url = $(this).attr('data-img');
				var win = window.open(url, '_blank');
				win.focus();
				console.log("Open in a new window " + url);
			} else if (key == "reload") {
				CFG_Continue = false ;
				this.nextAll().remove();
				this.remove();
				CFG_Restart = $(this).attr('data-page');
				console.log('Download restarted at image '+ CFG_Restart + '.'); 
			} else if (key == "hide") {
				//CFG_Continue = false ;
				this.prevAll().remove();
				console.log('Cleaning images before '+ $(this).attr('data-page') + '.'); 
			}
		},
		items: {
			"view": {name: "Voir l'image"},
			"sep1": "---------",
			"reload": {name: "Repartir de cette image"},
			"hide": {name: "Masquer les images précédentes"}
		}
	});
});





String.prototype.sansAccent = function(){
    var accent = [
        /[\300-\306]/g, /[\340-\346]/g, // A, a
        /[\310-\313]/g, /[\350-\353]/g, // E, e
        /[\314-\317]/g, /[\354-\357]/g, // I, i
        /[\322-\330]/g, /[\362-\370]/g, // O, o
        /[\331-\334]/g, /[\371-\374]/g, // U, u
        /[\321]/g, /[\361]/g, // N, n
        /[\307]/g, /[\347]/g // C, c
    ];
    var noaccent = ['A','a','E','e','I','i','O','o','U','u','N','n','C','c'];
     
    var str = this;
    for(var i = 0; i < accent.length; i++){
        str = str.replace(accent[i], noaccent[i]);
    }
     
    return str;
};