var CfgTimeoutTries = 3 ;
var CfgTimeoutOccured = 0 ;

function waitAndDownloadPage(duration) {
	duration = typeof duration !== 'undefined' ? duration : 0;
	// On attend entre 2 et 5 secondes, plus éventuellement un autre délai passé en paramètre
	var waitTime = duration + 2000 + Math.floor((Math.random() * 3000)) ;
	setTimeout(
		function(){downloadPageAjax();},
		waitTime
	);
}

function downloadPageAjax(){
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
			waitAndDownloadPage(0);
		},
		fail : function(data) {
			alert( "Une erreur est survenue (backend)." );
		},
		error : function(data, status, errorThrown ) {
			//console.info('Fail', data, status, errorThrown);
			if (data.responseText.search("Maximum execution time") > 0) {
				// Time out. On recommence.
				CfgTimeoutOccured = CFG_Timeout_occured + 1 ;
				if (CfgTimeoutOccured >= CfgTimeoutTries) {
					alert("Erreur de timeout ("+ CfgTimeoutTries+" tentatives).");
					return ;
				}
				// On attend 30 secondes et on repart
				waitAndDownloadPage(page, 30000);
			}
		}
	});
}



$(document).ready(function(){
  console.info("Initialising GallicaDownloader...");
  $('#projetsTable').DataTable({
    "columns" : [
    {"width" : "100px", "searchable" : false, "orderable" : false},
    {"width" : "180px"},
    {"width" : "420px"},
    {"width" : "70px", "searchable" : false, "orderable" : false},
    {"width" : "90px", "searchable" : false, "orderable" : false},
    {"width" : "50px", "searchable" : false, "orderable" : false}
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
    downloadPageAjax();
  }
});