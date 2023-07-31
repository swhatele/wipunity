$(document).ready(function () {
 $("body").click(function () {

        $(".post-dots .report").remove();
        $(".post-dots span").removeAttr("style");

        if (!$("#userlog").hasClass("hide")) {
            userToggle();
        }
         
    });
    $("#account_data_submit").on('click', function () {
        $('#acount_data_form').validate({
            rules: {
                name: {required: true},
                email: {required: true, email: true},
                uploaded_image: {required: true}
            },
            messages: {
                name: {required: "Please enter name."},
                email: {required: "Please enter email address.",
                    email: "Please enter valid email address."},
                uploaded_image: {required: "Please choose profile image "}
            },
            onkeyup: false,
            submitHandler: function (form) {

                $('#account_data_submit').hide();
                $('#account_data_loader').show();
                $.ajax({
                    url: APP_URL + "updateAccountrDetails",
                    data: new FormData($("#acount_data_form")[0]),
                    dataType: 'json',
                    async: true,
                    type: 'post',
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response.http_status == 200) {
                            $('#submitMessage').addClass('colorGreen').removeClass('colorRed').show().text(response.message);

                        } else {
                            $('#submitMessage').removeClass('colorGreen').addClass('colorRed').show().text(response.message);
                        }
                        setTimeout(function () {
                            $("#submitMessage").text('');
                        }, 2000);
                        $('#account_data_submit').show();
                        $('#account_data_loader').hide();
                    },
                    error: function (data) {
                        var json = jQuery.parseJSON(data['responseText']);
                        $('#submitMessage').removeClass('colorGreen').addClass('colorRed').show().text(json['message']);
                        setTimeout(function () {
                            $('#submitMessage').hide();
                            closePartDeletePopUp();
                        }, 10000);
                        $('#account_data_submit').show();
                        $('#account_data_loader').hide();
                    }
                });

            }
        });
    });

$("#save_notification").on('click', function () {
    $('#user_notification_settings_form').validate({
        rules: {
            notification_types: {required: true}
        },
        messages: {
            name: {required: "Please choose emial notifications type."}
        },
        onkeyup: false,
        submitHandler: function (form) {

            $('#save_notification').hide();
            $('#save_notification_loader').show();
            $.ajax({
                url: APP_URL + "updateNotificationSettings",
                data: new FormData($("#user_notification_settings_form")[0]),
                dataType: 'json',
                async: true,
                type: 'post',
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.http_status == 200) {
                        $('#save_notification_message').addClass('colorGreen').removeClass('colorRed').show().text(response.message);
                    } else {
                        $('#save_notification_message').removeClass('colorGreen').addClass('colorRed').show().text(response.message);
                    }
                    setTimeout(function () {
                        $("#save_notification_message").text('');
                    }, 2000);
                    $('#save_notification').show();
                    $('#save_notification_loader').hide();
                },
                error: function (data) {
                    var json = jQuery.parseJSON(data['responseText']);
                    $('#save_notification_message').removeClass('colorGreen').addClass('colorRed').show().text(json['message']);
                    setTimeout(function () {
                        $('#save_notification_message').hide();
                        
                    }, 10000);
                    $('#save_notification').show();
                    $('#save_notification_loader').hide();
                }
            });

        }
    });
    
    });

//confirm_password: {
//                required: true,
//                //                minlength: 8,
//                equalTo: "#profile_new_password"
//            }
//        },
    
    
     $("#change_password").on('click', function () {
        $('#change_password_form').validate({
            rules: {
                old_password: {required: true},
               password: {required: true, minlength: 8, validpassword: true},
            confirm_password: {required: true, minlength: 8, equalTo: "#password"},
            },
            messages: {
                old_password: {required: "Please enter old password."},
                password: {required: "Please enter password.", minlength: "Minimum 8 character required.",
                validpassword: "The password must contain a minimum of one lower case character," + " one upper case character, one digit and one special character"},
            confirm_password: {required: "Please reenter password.", minlength: "Minimum 8 character required.", equalTo: "Passwords do not match."},
            },
            onkeyup: false,
            submitHandler: function (form) {

                $('#change_password').hide();
                $('#change_password_loader').show();
                $.ajax({
                    url: APP_URL + "changePassword",
                    data: new FormData($("#change_password_form")[0]),
                    dataType: 'json',
                    async: true,
                    type: 'post',
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response.http_status == 200) {
                            $('#change_password_message').addClass('colorGreen').removeClass('colorRed').show().text(response.message);

                        } else {
                            $('#change_password_message').removeClass('colorGreen').addClass('colorRed').show().text(response.message);
                        }
                        setTimeout(function () {
                            $("#change_password_message").text('');
                        }, 2000);
                       $("#change_password_form").trigger("reset");
                        $('#change_password').show();
                        $('#change_password_loader').hide();
                    },
                    error: function (data) {
                        var json = jQuery.parseJSON(data['responseText']);
                        $('#change_password_message').removeClass('colorGreen').addClass('colorRed').show().text(json['message']);
                        setTimeout(function () {
                            $('#change_password_message').hide();
                            closePartDeletePopUp();
                        }, 10000);
                        $('#change_password').show();
                        $('#change_password_loader').hide();
                    }
                });

            }
        });
    });


$("#deactivate_account").on('click', function (){
    // forgot password form
    $('#deactivation_form').validate({
        rules: {
            reason: {required: true,}
        },
        messages: {
            reason: {required: "Please select deactivation reason."}
        },
        onkeyup: false,
        submitHandler: function (form) {
            var email = $('#reason').val();
            $('#deactivate_account').hide();
            $('#deactivate_account_loader').show();
            $.ajax({
                url: APP_URL + "deactivateAccount",
                data: new FormData($("#deactivation_form")[0]),
                dataType: 'json',
                async: true,
                type: 'post',
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.http_status == 200) {
                        $('#deactivate_account_message').addClass('colorGreen').removeClass('colorRed').show().text(response.message);
                        setTimeout(function () {
                                window.location = APP_URL + "/deactivate";

                            }, 1000);
                    } else {
                        $('#deactivate_account_message').removeClass('colorGreen').addClass('colorRed').show().text(response.message);
                    }
                    setTimeout(function () {
                        $("#deactivate_account_message").text('');
                    }, 2000);
                    $('#deactivate_account').show();
                    $('#deactivate_account_loader').hide();
                },
                error: function (data) {
                    var json = jQuery.parseJSON(data['responseText']);
                    $('#deactivate_account_message').removeClass('colorGreen').addClass('colorRed').show().text(json['message']);
                    setTimeout(function () {
                        $('#deactivate_account_message').hide();
                        
                    }, 10000);
                    $('#deactivate_account').show();
                    $('#deactivate_account_loader').hide();
                }
            });

        }
    });


});


})

function removeBg() {
    $(".pops").addClass("hide");
    $(".popup_mask").addClass("hide");
    $(".popup_mask_footer").addClass("hide");

}
function DeactivatePopUp() {
    $("#deactivateBlock").removeClass("hide");
    $(".popup_mask").removeClass("hide");
}

function accountManagement(url){
    window.location.href = url;
    
}

function userToggle(e) {
    event.stopPropagation();
    if ($("#tg-sbar").css("left") == "0px") {
        toggleSideNav();
    }
//    if (cBox.style.display === "flex") {
//        toggleChat();
//    }
    $(".post-dots .report").remove();
    $(".post-dots span").removeAttr("style");
    if (!$("#search").hasClass("hide")) {
        $("#search").addClass("hide");
    }
    if ($("#userlog").hasClass("hide")) {
        $("#userlog").removeClass("hide");
    } else {
        $("#userlog").addClass("hide");
    }
}
 

    