var CfgTimeoutTries = 3 ;
var CfgTimeoutOccured = 0 ;

function waitAndDownloadPage(duration, documentId) {
	duration = typeof duration !== "undefined" ? duration : 0;
	// On attend entre 0.5 et 1 secondes, plus éventuellement un autre délai passé en paramètre
	var waitTime = duration + 500 + Math.floor((Math.random() * 500)) ;
	setTimeout(
		function(){downloadPageAjax(documentId);},
		waitTime
	);
}

function waitAndUploadPage(duration, documentId, accessToken, parentFolderId) {
	duration = typeof duration !== "undefined" ? duration : 0;
	// On attend entre 0.5 et 1 seconde, plus éventuellement un autre délai passé en paramètre
	var waitTime = duration + 500 + Math.floor((Math.random() * 500)) ;
	setTimeout(
		function(){uploadPageAjax(documentId, accessToken, parentFolderId);},
		waitTime
	);
}

/********* Téléchargement Gallica ******************/
function downloadPageAjax(documentId){
	//console.info("Downloading...");
	// On demande le téléchargement d'une image
	$.ajax({
		url: "/download/next",
		type: "POST",
		data: {
			id : documentId
		},
		dataType: "json",
		success : function(data) {
			//console.log(data.message);
			$("#nbDownloaded").html(data.nbDownloaded);
			$("#nbTodo").html(data.nbTodo);
			$("#resultMessage").html(data.message);
			waitAndDownloadPage(0, documentId);
		},
		fail : function(data) {
			alert( "Une erreur est survenue (backend)." );
		},
		error : function(data, status, errorThrown ) {
			//console.info('Fail', data, status, errorThrown);
			if (data.responseText.search("Maximum execution time") > 0) {
				// Time out. On recommence.
				CfgTimeoutOccured = CfgTimeoutOccured + 1 ;
				if (CfgTimeoutOccured >= CfgTimeoutTries) {
					alert("Erreur de timeout ("+ CfgTimeoutTries+" tentatives).");
					return ;
				}
				// On attend 30 secondes et on repart
				waitAndDownloadPage(30000, documentId);
			}
		}
	});
}

/************* Envoi Google Drive *******************/
function uploadPageAjax(documentId, accessToken, parentFolderId){
	console.info("Uploading...");
	// On demande l'envoi d'une image
	$.ajax({
		url: "/googleDrive/next",
		type: "POST",
		data: {
			id : documentId,
      accessToken: accessToken,
      folderId: parentFolderId
		},
		dataType: "json",
		success : function(data) {
			console.log(data.message);
      if (data.result !== 'error') {
        $("#nbGoogleDrive").html(data.nbGoogleDrive);
        $("#nbDownloaded").html(data.nbDownloaded);
        $("#resultMessage").html(data.message);
        waitAndUploadPage(0, documentId, accessToken, parentFolderId);
      }
		},
		fail : function(data) {
			alert( "Une erreur est survenue (backend)." );
		},
		error : function(data, status, errorThrown ) {
			console.info('Fail', data, status, errorThrown);
			if (data.responseText.search("Maximum execution time") > 0) {
				// Time out. On recommence.
				CfgTimeoutOccured = CfgTimeoutOccured + 1 ;
				if (CfgTimeoutOccured >= CfgTimeoutTries) {
					alert("Erreur de timeout ("+ CfgTimeoutTries+" tentatives).");
					return ;
				}
				// On attend 1 minute et on repart
				waitAndUploadPage(60000, documentId, accessToken, parentFolderId);
			}
		}
	});
}


/************* DataTable : page index et liste des projets ****************/
$(document).ready(function(){
  //console.info("Initialising GallicaDownloader...");
  $("#projetsTable").DataTable({
    "columns" : [
    {"width" : "100px", "searchable" : false, "orderable" : false},
    {"width" : "180px"},
    {"width" : "420px"},
    {"width" : "60px", "searchable" : false, "orderable" : false},
    {"width" : "90px", "searchable" : false, "orderable" : false},
    {"width" : "90px", "searchable" : false, "orderable" : false}
    ],
    "order" : [[1, "asc"]],
    "pageLength": 15,
    "lengthChange": false,
    "info": false,
    "pagingType": "simple_numbers",
    "language": {
      "paginate": {
        "previous": "Précédent",
        "next": "Suivant"
      },
      "search": "Chercher",
      "zeroRecords": "Aucun résultat trouvé."
  }
  });
  
  // S'il y a une variable documentId, on est sur la page de téléchargement
  if (typeof documentId !== "undefined"){
    downloadPageAjax(documentId);
  }
  
  // S'il y a une variable googleDriveDocumentId, on est sur la page d'upload
  if (typeof googleDriveDocumentId !== "undefined" && typeof accessToken !== "undefined" && typeof parentFolderId !== "undefined"){
    uploadPageAjax(googleDriveDocumentId, accessToken, parentFolderId);
  }
});