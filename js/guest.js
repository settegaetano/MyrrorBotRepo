$(".messages").animate({ scrollTop: $(document).height() }, "fast");


var timestamp;
var imageURL;
var email;
var flagcitta= false;

var timestampStart = 0;//Quando l'utente invia la domanda
var timestampEnd = 0;//Quando l'utente clicca su Si/No

var theLanguage = $('html').attr('lang');
   var lang= lan_en;
  
  




function getEmail() {
  var email = "UtenteAnonimo";
  return email;
}

function getTimestampStart(){
  return timestampStart;
}

  $("#profile-img").click(function() {
  	$("#status-options").toggleClass("active");
  });


  function newMessage() {
  	message = $(".message-input input").val();
  	if($.trim(message) == '') {
  		return false;
  	}else{
  		 timestamp = new Date().getUTCMilliseconds();

       timestampStart = Date.now();//Utente invia il messaggio

  	$('<li class="sent"><img src="immagini/user.png" alt="" /><p id="quest'+timestamp+'"">' + message + '</p></li>').appendTo($('.messages ul'));
  	$('.message-input input').val(null);
  	$('.contact.active .preview').html('<span>Tu: </span>' + message);

    //Scroll verso il basso quando viene inviata una domanda
  	$(".messages").animate({ scrollTop:( $(document).height() * 100)}, "fast");

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
      temp += " "+ text;
      text = temp;
      }
      
      var citta = getCity();
      var name = "myrror";
var Language = $('html').attr('lang');
      //console.log(email);
      if(Language == 'it'){
       lang = lan_it;
    }else{
      lang = lan_en;
    }
  
     if (text.match(lang.change) || text.match(lang.change1) || text.match(lang.change2) || text.match(lang.change3) || text.match(lang.change4)
        || text.match(lang.change5) || text.match(lang.change6) || text.match(lang.change7) || text.match(lang.change8) || text.match(lang.change9)
        || text.match(lang.another) || text.match(lang.another2) || text.match(lang.another3) || text.match(lang.another4) || text.match(lang.another5) || text.match(lang.another6)) {

      text = $('#contesto').val();
    }else{
       if(flagcitta == false){
        $('#contesto').val(text);
       }
   
    }

    $.ajax({
        type: "POST",
        url: "php/intentGuest.php",
        data: {testo:text,city:citta,mail:email,lang:theLanguage},
        success: function(data) {
          setResponse(data);
        }
    });
    
         
  }

function setResponse(val) {
var string = val;
      console.log(val);
    
      if (/^[\],:{}\s]*$/.test(val.replace(/\\["\\\/bfnrtu]/g, '@').
replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']').
replace(/(?:^|:|,)(?:\s*\[)+/g, ''))) {

  //the json is ok
 val = JSON.parse(val);
      var musicaSpotify = lang.spotify1;
      var spiegazione = "";

      var canzoneNomeSpotify = lang.spotify2;
      var canzoneArtistaSpotify = lang.spotify2;
      var canzoneGenereSpotify = lang.spotify3;
      var playlistEmozioniSpotify = lang.spotify4;
      var canzoneEmozioniSpotify = lang.spotify5;
      var canzoniPersonalizzateSpotify = lang.spotify6;
      var video = lang.video1;

      if(val["intentName"] == "attiva debug"){
        	setDebug(true);
        	$(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p >'+val["answer"]+'</p></li>');
   
      }else if(val["intentName"] == "disattiva debug"){
            setDebug(false);
            $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p >'+val["answer"]+'</p></li>');
   
      }else if(val['intentName'] == "Musica"){
        
        if (val['answer']['url'] == ""){
              $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p>'+lang.login1+'</p></li>');
         }else{
            if (val['answer']['explain'] != ""){
            spiegazione = val['answer']['explain'];
            $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p>' + spiegazione + "<br>" + musicaSpotify + '&#x1F603;' +'<br>'+ '<iframe src="' + val['answer']['url'] + '" width="250" height="380" frameborder="0" allowtransparency="true" allow="encrypted-media"></iframe></p></li>');
          } else{
            $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p id="par'+timestamp+'">'+ musicaSpotify + '&#x1F603;' +'<br>'+ '<iframe src="' + val['answer']['url'] + '" width="250" height="380" frameborder="0" allowtransparency="true" allow="encrypted-media"></iframe></p></li>');
          }
        }

      } else if(val["intentName"] == "News" ){


          if (val['answer'] == ""){
              $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p>'+lang.login1+'</p></li>');
         }else{
            if (val['answer']['explain'] != null){
              spiegazione = val['answer']['explain'];
              $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p id="par'+timestamp+'"> ' + spiegazione + '<br><img style="width: 100%;height: 100%;" src= "'+val['answer']['image']+'"/><a href="'+val['answer']['url']+'">'+val["answer"]['title']+'</a></p></li>');

            }else{
              $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p id="par'+timestamp+'"><img style="width: 100%;height: 100%;" src= "'+val['answer']['image']+'"/><a href="'+val['answer']['url']+'">'+val["answer"]['title']+'</a></p></li>');
            }
         }                 
       
      }else if(val["intentName"] == "Ricerca Video"){


          $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p id="par'+timestamp+'">' + video + ' &#x1F603; <br>' +'<iframe id="ytplayer" type="text/html" width="260" height="260" src="' + val['answer']['ind'] + '" frameborder="0" allowfullscreen/></iframe></p></li>');
           if (val['answer']['explain'] != undefined && val['answer']['explain'] != null){
               $('#spiegazione').val(val['answer']['explain']);
         
           }
          
      }else if((val["intentName"] == "Meteo" ) && val['confidence'] > 0.60 ){

           if(getCity() == "" && val['answer']['city'] == undefined ){
           $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p >'+lang.inscity+'</p></li>');
           flagcitta = true;
        }

        
        var json = val['answer']['res'];
       
           if( json == ""){
          $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p >'+lang.unfor3+'</p></li>');
      
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
            '<li id="par'+timestamp+'" class="replies"><img src="immagini/chatbot.png" alt="" /><p ><div class="container">'+
            '<div class="forecast-container" id= "f'+timestamp+'"><div class="today forecast">'+
            '<div class="forecast-header"><div class="day">'+str[0]+'</div></div>'+
            '<div class="forecast-content"><div class="location">'+ val['answer']['city']+' Ore '+str[1]+'</div><div class="degree">'+
            '<div class="num">'+Math.trunc( str[2])+'<sup>o</sup>C</div><div class="forecast-icon">'+
            '<img src="immagini/icons/'+imglink+'" alt="" style="width:90px;"> </div></div>'+
            '</div></div></div> </p></div></li>'
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
          var answer = val['answer'] +lang.register+"  <a href='http://90.147.102.243:9090'>90.147.102.243</a>";
          $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p>' + answer + '</p></li>');
      }

      if(val['intentName'] == "Default Welcome Intent"){

      }else if(isDebugEnabled()){
        var risposta = val['answer'];
        risposta = risposta.toString().toLowerCase();
        if(val['confidence'] < 0.6  || risposta.includes(lang.retry) || risposta.includes(lang.unfor)
          || risposta.includes(lang.unfor2) || risposta == "" ){   
             
            
      
            var testo  = $("#hide"+timestamp).text();
            var mail = getEmail();
            var question = $( "#quest"+timestamp ).text();
            //var timestampStart = getTimestampStart();
            var timestampEnd = Date.now();
            rating(testo,question,'no',mail,timestampStart,timestampEnd,"");
        }else{
           $('#par'+timestamp).append('<div class="rating-box"><h4>'+lang.satisfy+'</h4><button id="yes'+timestamp+'" class="btn-yes">'+lang.yes+'</button>'+
        '<button id="no'+timestamp+'" class="btn-no">NO</button></div>');
        
        }


       $(".chat").append('<p hidden id="hide'+timestamp+'" >'+string+'<p/>');
      }

}else{

  //the json is not ok
    $(".chat").append('<li class="replies"><img src="immagini/chatbot.png" alt="" /><p >'+lang.donotunderstand+'</p></li>');
      

}

       $(".messages").animate({ scrollTop:( $(document).height() * 100)  }, "fast");
      
    //$(".chat").append('<p  id="hide'+timestamp+'" hidden> "'+quest+'"<p/>');
    }

    //Intent avviato all'inizio del dialogo per mostrare la frase di benvenuto e per impostare il nome dell'utente nella schermata
    function welcomeIntent(){

var Language = $('html').attr('lang');
      //console.log(email);
      if(Language == 'it'){
       lang = lan_it;
    }else{
      lang = lan_en;
    }

    send(lang.help);
      var value = "; " + document.cookie;
if (value.match(/myrror/)) {
    var parts = value.split("; " + name + "=");   
    var tempStr = null;
   while(tempStr == null){
    tempStr =  parts.pop().split(";").shift();
     if(tempStr.match(/@/)){
        window.location.href = 'chatbot.html';
     }
   }

}else{
 // window.location.href = 'index.html';
}

      
      
    }

      

