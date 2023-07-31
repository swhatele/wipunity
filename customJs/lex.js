// set the focus to the input box
document.getElementById("wisdom").focus();


// Initialize the Amazon Cognito credentials provider
AWS.config.region = 'us-west-2'; // Region
AWS.config.credentials = new AWS.CognitoIdentityCredentials({
    // Provide your Pool Id here
    IdentityPoolId: 'us-west-2:3f9a57d4-8ba6-4b92-9040-44e4c6aba998',
});

var lexruntime = new AWS.LexRuntime();
var lexUserId = 'chatbot-demo' + Date.now();
var sessionAttributes = {};
var location_data = false;
var select_hud_term = false;
var bot_hud_term = '';
var intentName = '';
$(document).ready(function () {

    $('input[name="hud_term"]:radio').change(function () {

        $("#suggestionText").remove();
        $(".lexError").remove();
        var hud_term = $('input:radio[name=hud_term]:checked').val();
        var help_text = ''; 

        if (hud_term == 'WaterQuality') {
            help_text = '<div class="sendBlock" id="suggestionText"><img src="' + APP_URL + 'resources/assets/images/chat.png"><span>What do you want to know about water quality?</span></div>';
        } else if (hud_term == 'Impervious') {
            help_text = '<div class="sendBlock" id="suggestionText"><img src="' + APP_URL + 'resources/assets/images/chat.png"><span>What do you want to know about impervious?</span></div>';
        } else if (hud_term == 'Ecodeficit') {
            help_text = '<div class="sendBlock" id="suggestionText"><img src="' + APP_URL + 'resources/assets/images/chat.png"><span>What do you want to know about ecodeficit?</span></div>';
        } else if (hud_term == 'Population') {
            help_text = '<div class="sendBlock" id="suggestionText"><img src="' + APP_URL + 'resources/assets/images/chat.png"><span>What do you want to know about population?</span></div>';
        } else if (hud_term == 'Withdrawal') {
            help_text = '<div class="sendBlock" id="suggestionText"><img src="' + APP_URL + 'resources/assets/images/chat.png"><span>What do you want to know about withdrawal?</span></div>';
        } else if (hud_term == 'Discharge') {
            help_text = '<div class="sendBlock" id="suggestionText"><img src="' + APP_URL + 'resources/assets/images/chat.png"><span>What do you want to know about discharge?</span></div>';
        } else if (hud_term == 'Risk') {
            help_text = '<div class="sendBlock" id="suggestionText"><img src="' + APP_URL + 'resources/assets/images/chat.png"><span>What do you want to know about risk?</span></div>';
        }


        $("#conversation").append(help_text);
         saveFinnData(help_text,'1');
          saveHUDTerm(hud_term);
        var conversationDiv = document.getElementById('conversation');
        conversationDiv.scrollTop = conversationDiv.scrollHeight;
        $("#chat_welcome_note").addClass('hide');
        return false;

    });
});
function pushChat() {

    $(".lexError").remove();

    if ($('input[name="hud_term"]:checked').length == 0) {
        console.log(hud_term);
        showError("Please select HUD Term.");
        return false;
    }
    var bot_hud_term = $('input:radio[name=hud_term]:checked').val();

    // if there is text to be sent...
    var wisdomText = document.getElementById('wisdom');
//    var hud_term = document.getElementById('hud_term');
    console.log(wisdomText.value.trim());
    //suggestions for users  


    if (wisdomText && wisdomText.value && wisdomText.value.trim().length > 0) {

        // disable input to show we're sending it
        $('.chat_send').css('pointer-events', 'none');
        var wisdom = wisdomText.value.trim();
        wisdomText.value = '...';
        wisdomText.locked = true;
        // send it to the Lex runtime

//        console.log(wisdom); 
//            console.log(bot_hud_term);
        var params = {
            botAlias: '$LATEST',
            botName: bot_hud_term,
            inputText: wisdom,
            userId: lexUserId,
            sessionAttributes: sessionAttributes
        };
        showRequest(wisdom);
        saveFinnData(wisdom,'2');
        lexruntime.postText(params, function (err, data) {
            if (err) {
                console.log(err, err.stack);
//                    showError('Error:  ' + err.message);
                showError('Something went wrong, please try again.');

            }

            if (data) {
                // capture the sessionAttributes for the next cycle
                sessionAttributes = data.sessionAttributes;
                console.log(sessionAttributes);
                // show response and/or error/dialog status 
                console.log(data.intentName);
                intentName = data.intentName;
                if (data.intentName == null) {
                    saveQuestion(wisdom, bot_hud_term);
                }
                showResponse(data);
                saveFinnData(data.message,'3');
            }
            // re-enable input
            wisdomText.value = '';
            wisdomText.locked = false;
            $('.chat_send').css('pointer-events', 'auto');
        });
//        }


        // we always cancel form submission
        return false;
    } else {
        showError('Please post your question here?');
    }


} 

function saveQuestion(wisdom, hud_term) {
    $.ajax({
        url: APP_URL + "saveUnansweredQuestion",
        dataType: 'json',
        type: 'post',
//                                        contentType: "application/json",
        data: {'question': wisdom, 'hud_term': hud_term},
        success: function (response) {
            return true;
        },
        error: function (data) {
            console.log(data);

        }
    });
    return true;
}

function saveFinnData(info, type) {
    $.ajax({
        url: APP_URL + "saveFINNData",
        dataType: 'json',
        type: 'post', 
        data: {'question': info,'type': type},
        success: function (response) {
            return true;
        },
        error: function (data) {
            console.log(data);

        }
    });
    return true;
}

function saveHUDTerm(hud_term){
     $.ajax({
        url: APP_URL + "saveHUDTerm",
        dataType: 'json',
        type: 'post',
//                                        contentType: "application/json",
        data: {'hud_term': hud_term},
        success: function (response) {
            return true;
        },
        error: function (data) {
            console.log(data);

        }
    });
    return true;
}
