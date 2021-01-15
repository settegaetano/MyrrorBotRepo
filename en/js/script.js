$(".messages").animate({ scrollTop: $(document).height() }, "fast");
var timestamp;
var imageURL;
var email;
var flagcitta= false;
var question;

function getEmail() {
	return email;
}

function getTimestampStart(){
  return timestampStart;
}

  $("#profile-img").click(function() {
  	$("#status-options").toggleClass("active");
  });
/*
  $(".expand-button").click(function() {
    $("#profile").toggleClass("expanded");
  	$("#contacts").toggleClass("expanded");
  });

  $("#status-options ul li").click(function() {
  	$("#profile-img").removeClass();
  	$("#status-online").removeClass("active");
  	$("#status-away").removeClass("active");
  	$("#status-busy").removeClass("active");
  	$("#status-offline").removeClass("active");
  	$(this).addClass("active");
  	
  	if($("#status-online").hasClass("active")) {
  		$("#profile-img").addClass("online");
  	} else if ($("#status-away").hasClass("active")) {
  		$("#profile-img").addClass("away");
  	} else if ($("#status-busy").hasClass("active")) {
  		$("#profile-img").addClass("busy");
  	} else if ($("#status-offline").hasClass("active")) {
  		$("#profile-img").addClass("offline");
  	} else {
  		$("#profile-img").removeClass();
  	};
  	
  	$("#status-options").removeClass("active");
  });


   $("#toggle").unbind().click(function(){

var text = $(this).html();
if (text == "Disattiva modalità di debug"){
    $(this).css("background-color","red");
  $(this).html("Attiva modalità di debug");
  debug = false;
}else{
  $(this).css("background-color","green");
  $(this).html("Disattiva modalità di debug");
  debug = true;
}
//$(this).attr("disabled", true);

});
   */

   $("#logout").click(function(){
    $.cookie("myrror"+getEmail(), null, { path: '/' });
     $.removeCookie('myrror'+getEmail(), { path: '/' });
   });
   

  function newMessage() {
  	message = $(".message-input input").val();
  	if($.trim(message) == '') {
  		return false;
  	}else{
  		 timestamp = new Date().getUTCMilliseconds();
      
      timestampStart = Date.now();//Utente invia il messaggio

  	$('<li class="sent"><img src="'+imageURL+'" alt="" /><p id="quest'+timestamp+'"">' + message + '</p></li>').appendTo($('.messages ul'));
  	$('.message-input input').val(null);
  	$('.contact.active .preview').html('<span>You: </span>' + message);

    //Scroll verso il basso quando viene inviata una domanda
  	$(".messages").animate({ scrollTop:( $(document).height() * 100)}, "fast");
    question = message;
    return message;
  	}

   
  };

   


  $(document).on("click", "button.submit", function () {
   //all the action
   $('button.submit').off('click');
     var  query = newMessage();
    if (query == false) {
      return false;
    }else{
      send(query);
       return true;
    }
   
});

  //Quando viene premuto 'invio' sulla tastiera
  $(window).on('keydown', function(e) {
    if (e.which == 13) {
      let query = newMessage();
      send(query);

      return false;
    }
  });

 function send(query) {
      var text = query;

      if(flagcitta == true){
         flagcitta = false;
         temp = $('#contesto').val();
         temp += " in "+ text;
         text = temp;
      }
      var citta = getCity();
      var name = "myrror";

      var value = "; " + document.cookie;
if (value.match(/myrror/)) {
      var parts = value.split("; " + name + "=");   
      var tempStr = null;
      while(tempStr == null){
        tempStr =  parts.pop().split(";").shift();
        if(tempStr.match(/@/)){
          //alert(tempStr);
        }
      }  

}else{
  window.location.href = 'index.html';
}
  
   
    email = tempStr;
   
    if(text.match(/why/) || text.match(/explain/)){
         var testo = $("#spiegazione").val();
          $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p >'+testo+'</p></li>');
   
    }else{
       
  

    if (text.match(/change/) || text.match(/another/) || text.match(/another one/) || text.match(/read me another one/) || text.match(/give me another one/)
      || text.match(/read other/) || text.match(/read another news/) || text.match(/change song/) || text.match(/change track/) || text.match(/change another track/)
      || text.match(/change video/) || text.match(/give me another video/) || text.match(/give me another track/) || text.match(/give me another song/) || text.match(/change music/) || text.match(/change news/)) {

      text = $('#contesto').val();
    }else{
       if(flagcitta == false){
        $('#contesto').val(text);
       }
      
    }

    $.ajax({
              type: "POST",
              url: "php/intentDetection.php",
              data: {testo:text,city:citta,mail:email},
              success: function(data) {
                setResponse(data);
              }
            });
    }
         
  }

function setResponse(val) {
var string = val;
      console.log(val);
    
      if (/^[\],:{}\s]*$/.test(val.replace(/\\["\\\/bfnrtu]/g, '@').
replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']').
replace(/(?:^|:|,)(?:\s*\[)+/g, ''))) {

  //the json is ok
 val = JSON.parse(val);
    var musicaSpotify = "Here is your request!";
      var spiegazione = "";

      var canzoneNomeSpotify = "Here is the song requested!";
      var canzoneArtistaSpotify = "Here is the song of the requested artist!";
      var canzoneGenereSpotify = "Here is a playlist of songs of the required genre!";
      var playlistEmozioniSpotify = "Here is a playlist of songs recommended based on your mood";
      var canzoneEmozioniSpotify = "Here is a suggested song based on your mood";
      var canzoniPersonalizzateSpotify = "Here is a recommended song you might like";
      var video = "Here is the video requested";

      if (val["intentName"] == "Interessi" || val["intentName"] == "Contatti" || val["intentName"] == "Esercizio fisico" || val["intentName"] == "Personalita") {
       
         $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p id="par'+timestamp+'">' + val["answer"] + '</p></li>');

      }else if(val["intentName"] == "attiva debug"){
        	setDebug(true);
        	  $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p >'+val["answer"]+'</p></li>');
   
      }else if(val["intentName"] == "disattiva debug"){
            setDebug(false);
              $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p >'+val["answer"]+'</p></li>');
   
      }else if(val['intentName'] == "Musica"){
        

        if (val['answer']['url']['answer'] == ""){
              $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p> recommendations have not been generated yet</p></li>');
         }else{
          $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p id="par'+timestamp+'">'+ musicaSpotify + '&#x1F603;' +'<br>'+ '<iframe src="' + val['answer']['url'] + '" width="250" height="380" frameborder="0" allowtransparency="true" allow="encrypted-media"></iframe></p></li>');
        }

        if (val['answer']['explain'] != ""){
          spiegazione = val['answer']['explain'];
          $("#spiegazione").val(spiegazione);
          //$(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p id="par'+timestamp+'"> "<br>"' + musicaSpotify + '&#x1F603;' +'<br>'+ '<iframe src="' + val['answer']['url'] + '" width="250" height="380" frameborder="0" allowtransparency="true" allow="encrypted-media"></iframe></p></li>');
        }
          
      }else if(val["intentName"] == "News" ){


          if (val['answer'] == ""){
              $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p> recommendations have not been generated yet</p></li>');
         }else{
            if (val['answer']['explain'] != undefined && val['answer']['explain'] != null){
              spiegazione = val['answer']['explain'];
              $("#spiegazione").val(spiegazione);
             // $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p id="par'+timestamp+'"> ' + spiegazione + '<br><img style="width: 100%;height: 100%;" src= "'+val['answer']['image']+'"/><a href="'+val['answer']['url']+'">'+val["answer"]['title']+'</a></p></li>');

            }
              $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p id="par'+timestamp+'"><img style="width: 100%;height: 100%;" src= "'+val['answer']['image']+'"/><a id="nw'+timestamp+'" class="news" target="_blank" href="'+val['answer']['url']+'">"'+val["answer"]['title']+'"</a></p></li>');
              $(".chat").append('<input value="false" type="hidden" id= "flagNews'+timestamp+'" >')
         }                 
       
      }else if(val["intentName"] == "Video in base alle emozioni" || val["intentName"] == "Ricerca Video"){


          $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p id="par'+timestamp+'">' + video + ' &#x1F603; <br>' +'<iframe id="ytplayer" type="text/html" width="260" height="260" src="' + val['answer']['ind'] + '" frameborder="0" allowfullscreen/></iframe></p></li>');
           if (val['answer']['explain'] != undefined && val['answer']['explain'] != null){
               $('#spiegazione').val(val['answer']['explain']);
         
           }
          
      }else if (val["intentName"] == "meteo binario" ) {

        if(getCity() == "" && val['answer']['city'] == undefined ){
           $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p >Enter city</p></li>');
           flagcitta = true;
        }else{
          $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p >'+ val['answer']['res']+'</p></li>');
        }


      }else if((val["intentName"] == "DeletePreference" ) && val['confidence'] > 0.60 ){

          var sum = '';
          var i = 1;
          $.each(val["answer"], function( index, value ){
              sum += '<li><p id = "pc' + i + '">' + value + '</p><span id = "spc' + i + '" class="close">&times;</span></li>';
              i++;
          });

         $(".chat").append(//'<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>'+
            '<li id="par' + timestamp + '" class="replies"><img src="immagini/chatbot.png" alt="" /><div class="container"><ul>'+
              sum + 
            '</ul></div></li>'
            );


      }else if((val["intentName"] == "Meteo" ) && val['confidence'] > 0.60 ){

        if(getCity() == "" && val['answer']['city'] == undefined ){
           $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p >Enter city</p></li>');
           flagcitta = true;
        }
        var json = val['answer']['res'];
       
           if( json == ""){
          $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p >Unfortunately, no data is available regarding the indicated period</p></li>');
      
           }else{
              var res = json.split("<br>");
           var str = res[0].split(";");
         
            var imglink = "";

            switch(str[3]){

              case 'cielo sereno':
              imglink = "icon-2.svg";
              break;

              case 'poco nuvoloso':
              imglink = "icon-1.svg";
              break;

              case 'parzialmente nuvoloso':
              imglink = "icon-7.svg";
              break;

              case 'nubi sparse':
              imglink = "icon-6.svg";
              break;

              case 'pioggia leggera':
              imglink = "icon-4.svg";
              break;

              case 'nuvoloso':
              imglink = "icon-5.svg";
              break;

              case 'piogge modeste':
              imglink = "icon-9.svg";
              break;

              case 'pioggia pesante':
              imglink = "icon-11.svg";
              break;

              case 'neve leggera':
              imglink = "icon-13.svg";
              break;

              case 'neve':
              imglink = "icon-14.svg";
              break;



            }

           $(".chat").append(//'<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>'+
            '<li id="par'+timestamp+'" class="replies"><img src="immagini/chatbot.png" alt="" /><div class="container">'+
            '<div class="forecast-container" id= "f'+timestamp+'"><div class="today forecast">'+
            '<div class="forecast-header"><div class="day">'+str[0]+'</div></div>'+
            '<div class="forecast-content"><div class="location">'+ val['answer']['city']+' Ore '+str[1]+'</div><div class="degree">'+
            '<div class="num">'+Math.trunc( str[2])+'<sup>o</sup>C</div><div class="forecast-icon">'+
            '<img src="immagini/icons/'+imglink+'" alt="" style="width:90px;"> </div></div>'+
            '</div></div></div></div></li>'
            //+'<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>'
            );

           str = null;
           for (var i = 1; i < res.length-1; i++) {

              var str =  res[i].split(";");
              var imglink = "";

            switch(str[3]){

              case 'cielo sereno':
              imglink = "icon-2.svg";
              break;

              case 'poco nuvoloso':
              imglink = "icon-1.svg";
              break;

              case 'parzialmente nuvoloso':
              imglink = "icon-7.svg";
              break;

              case 'nubi sparse':
              imglink = "icon-6.svg";
              break;

              case 'pioggia leggera':
              imglink = "icon-4.svg";
              break;

              case 'nuvoloso':
              imglink = "icon-5.svg";
              break;

              case 'piogge modeste':
              imglink = "icon-9.svg";
              break;

              case 'pioggia pesante':
              imglink = "icon-11.svg";
              break;

              case 'neve leggera':
              imglink = "icon-13.svg";
              break;

              case 'neve':
              imglink = "icon-14.svg";
              break;

            }

              $('#f'+timestamp).append('<div class="forecast">'+
              '<div class="forecast-header"><div class="day">'+str[1]+'</div>'+
              '</div><div class="forecast-content"> <div class="forecast-icon">'+
              '<img src="immagini/icons/'+imglink+'" alt="" style="width:40px;"></div>'+
              '<div class="degree">'+Math.trunc( str[2] )+'<sup>o</sup>C</div></div></div>');
             
            } 
          }
        }else {
          $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p id="par'+timestamp+'">' + val["answer"] + '</p></li>');
      }

      if(val['intentName'] == "Default Welcome Intent"){
      
      }else if(val["intentName"] == "News"  ){
           $('#par'+timestamp).append('<div class="rating-box"><h4>Did you liked it?</h4><button id="yes'+timestamp+'" class="btnlike">SI</button>'+
        '<button id="no'+timestamp+'" class="btndislike">NO</button></div>');

      }else if(val["intentName"] == "Musica"){
           $('#par'+timestamp).append('<div class="rating-box"><h4>Did you like the artist?<p id="art'+ timestamp + '">'+ val["answer"]["param"]+'</p></h4><button id="yes'+timestamp+'" class="btnlikeMusic">SI</button>'+
        '<button id="no'+timestamp+'" class="btndislikeMusic">NO</button></div>');

      }else if(isDebugEnabled()){
        var risposta = val['answer'];
        risposta = risposta.toString().toLowerCase();
        if(val['confidence'] < 0.6  || risposta.includes('try again') || risposta.includes('unfortunately')
          || risposta == "" ){   
             

      
            var testo  = $("#hide"+timestamp).text();
            var mail = getEmail();
            var question = $( "#quest"+timestamp ).text();
            //var timestampStart = getTimestampStart();
            var timestampEnd = Date.now();
            rating(testo,question,'no',mail,timestampStart,timestampEnd,"");
        }else{
           $('#par'+timestamp).append('<div class="rating-box"><h4>Are you satisfied about the answer?</h4><button id="yes'+timestamp+'" class="btn-yes">YES</button>'+
        '<button id="no'+timestamp+'" class="btn-no">NO</button></div>');
        
        }


       $(".chat").append('<p hidden id="hide'+timestamp+'" >'+string+'<p/>');
      }

}else{

  //the json is not ok
    $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p >I do not understand what you mean. Try asking your question with other words!</p></li>');
      

}

       $(".messages").animate({ scrollTop:( $(document).height() * 100)  }, "fast");
      
    //$(".chat").append('<p  id="hide'+timestamp+'" hidden> "'+quest+'"<p/>');
    }

    //Intent avviato all'inizio del dialogo per mostrare la frase di benvenuto e per impostare il nome dell'utente nella schermata
    function welcomeIntent(){
      send("help");
      var value = "; " + document.cookie;
if (value.match(/myrror/)) {
    var parts = value.split("; " + name + "=");   
    var tempStr = null;
   while(tempStr == null){
    tempStr =  parts.pop().split(";").shift();
     if(tempStr.match(/@/)){
        //alert(tempStr);
     }
   }

}else{
  window.location.href = 'index.html';
}
setProfileImg(tempStr);
setNominativo(tempStr); //Nome per la grafica del sito
      
      
    }

      function setProfileImg(email){
    
          $.ajax({
        type: "POST",
        url: "php/getProfileImage.php",
        data: {mail:email},
        success: function(data) {
          imageURL = data;
          $('#profile-img').attr('src',imageURL);
        }
      });

}

    //Funzione usata per impostare il nome dell'utente nella schermata
    function setNominativo(tempStr) {
      $.ajax({
        type: "POST",
        url: "php/setNominativo.php",
        data:{mail:tempStr},
        success: function(data) {
          console.log(data);
          $(".nomeUtente").append(data);
        }
      });
    }


$("ul.chat").on("click","a.news",function(evnt) {
 
    var id = $(this).attr("id");
    id = id.substr(2,timestamp.length);

    $("#flagNews"+id).val("true");
    //alert("clicked");
    //return false;
});

$("#logout").click(function(){
  window.location.href = 'index.html';
});

//Click sulle preferenze da rimuovere
$("ul.chat").on("click","span.close",function(evnt) {
  
  var id = $(this).attr("id");
  id = id.replace('spc','pc');
  //console.log(id);

  var par = $('#'+id).text();
  //console.log(par);


  $(this).parent().removeAttr('style').hide(); //Al click sulla 'X' rimuovo la preferenza dal display

  //var token = getCookie('token');
  //console.log(token);

  //Passo i dati al php (value:par)
   $.ajax({
        type: "POST",
        url: "php/removeInterest.php",
        data:{mail:email,value:par},
        success: function(data) {
          console.log(data);
        }
      });



});



setInterval(function(){ 
 
    var value = "; " + document.cookie;
      var token;
      if (value.match(/x-access-token/)) {
            var parts = value.split("; " + 'x-access-token' + "="); 

            var tempStr = null;
            while(tempStr == null){
              tempStr =  parts.pop().split(";").shift();
              
            } 
           token = tempStr;
      }
//console.log(token);
 $.ajax({
          type: "POST",
          url: "php/JSON_upload.php",
          data: {accesstoken: token,mail:email},
        success: function(data) {
          console.log(data);
        }
        });

}, 40000);



function logNews(mail,url,title,rate){

  var value = "; " + document.cookie;
    var name = "technique"
    var technique = "W2V";
  
    if (value.match(/technique/)) {
        var parts = value.split("; " + name + "=");   
        technique =  parts.pop().split(";").shift(); 
            
    }

  $.ajax({
    type: "POST",
    url: "../php/recommender_log.php",
    data: {title:title, url:url, rating:rate, tec:technique, email:mail},
    success:function(data){
    console.log(data);
    }


  });

}

function logMusic(mail,artista,rate){

  var value = "; " + document.cookie;
    var name = "technique"
    var technique = "W2V";
  
    if (value.match(/technique/)) {
        var parts = value.split("; " + name + "=");   
        technique =  parts.pop().split(";").shift(); 
            
    }


  $.ajax({
    type: "POST",
    url: "../php/recommender_log.php",
    data: {artist:artista, rating:rate, tec:technique, email:mail},
    success:function(data){
    console.log(data);
    }


  });


}

$("ul.chat").on("click","button.btnlike",function(evnt) {

    
    var timestamp = $(this).attr("id");
    $(this).attr("disabled", true);
    var mail = getEmail();
    timestamp = timestamp.substr(3,timestamp.length); 
    
    $("#no"+timestamp).attr("disabled", true);
    var testo  = $("#nw"+timestamp).html();
    var link = $("#nw"+timestamp).attr('href');
     var valutazione = "1";


   $.ajax({
        type: "POST",
        url: "php/insertArticle.php",
        data:  {mail:email,url:link,descrizione:testo,like:valutazione},
        success: function(data) {
            console.log(data);
        }
      }); 
    
  logNews(mail,link,testo,valutazione);


});


$("ul.chat").on("click","button.btndislike",function(evnt) {

  var timestamp = $(this).attr("id");
    $(this).attr("disabled", true);
    var mail = getEmail();
    timestamp = timestamp.substr(2,timestamp.length); 
    
    $("#yes"+timestamp).attr("disabled", true);
    var testo  = $("#nw"+timestamp).html();
    var link = $("#nw"+timestamp).attr('href');
    var valutazione = "0";


    logNews(mail,link,testo,valutazione);



});


$("ul.chat").on("click","button.btnlikeMusic",function(evnt) {

    var timestamp = $(this).attr("id");
    $(this).attr("disabled", true);
    var mail = getEmail();
    timestamp = timestamp.substr(3,timestamp.length); 
    
    $("#no"+timestamp).attr("disabled", true);
    var artista  = $("#art"+timestamp).html();
    //var link = $("#nw"+timestamp).attr('href');
    var valutazione = "1";

   $.ajax({
        type: "POST",
        url: "php/musicFeedback.php",
        data:  {mail:email,artista:artista,like:valutazione},
        success: function(data) {
            console.log(data);
        }
      }); 

   logMusic(mail,artista,valutazione);
    
});


$("ul.chat").on("click","button.btndislikeMusic",function(evnt) {

    var timestamp = $(this).attr("id");
    $(this).attr("disabled", true);
    var mail = getEmail();
    timestamp = timestamp.substr(2,timestamp.length); 
    
    $("#yes"+timestamp).attr("disabled", true);
    var artista  = $("#art"+timestamp).html();
    //var link = $("#nw"+timestamp).attr('href');
    var valutazione = "0";

   $.ajax({
        type: "POST",
        url: "php/musicFeedback.php",
        data:  {mail:email,artista:artista,like:valutazione},
        success: function(data) {
            console.log(data);
        }
      }); 

   logMusic(mail,artista,valutazione);
    
});