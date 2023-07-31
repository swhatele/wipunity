$(document).ready(function () {
    $('.tabs').tabs();
});
$(document).ready(function () {
    $('#visibileOn').on("click", function () {
        $("#visibileOff").show();
        $("#visibileOn").hide();
        $('#login_password').attr('type', 'password');
    });
    $('#visibileOff').on("click", function () {
        $("#visibileOn").show();
        $("#visibileOff").hide();
        $('#login_password').attr('type', 'text');
    });
    $('#signup-visibileOn').on("click", function () {
        $("#signup-visibileOff").show();
        $("#signup-visibileOn").hide();
        $('#password').attr('type', 'password');
    });
    $('#signup-visibileOff').on("click", function () {
        $("#signup-visibileOn").show();
        $("#signup-visibileOff").hide();
        $('#password').attr('type', 'text');
    });
    $('#signup-rtype-visibileOn').on("click", function () {
        $("#signup-rtype-visibileOff").show();
        $("#signup-rtype-visibileOn").hide();
        $('#confirm_password').attr('type', 'password');
    });
    $('#signup-rtype-visibileOff').on("click", function () {
        $("#signup-rtype-visibileOn").show();
        $("#signup-rtype-visibileOff").hide();
        $('#confirm_password').attr('type', 'text');
    });


    //Sign up step-1

    $('#signup_form').validate({
        rules: {
            first_name: {required: true},
            last_name: {required: true},
            email: {required: true, email: true},
            password: {required: true, minlength: 8, validpassword: true},
            confirm_password: {required: true, minlength: 8, equalTo: "#password"},
            company_name: {required: true},
            title: {required: true}
        },
        messages: {
            first_name: {required: "Please enter first name."},
            last_name: {required: "Please enter last name."},
            email: {required: "Please enter email address.",
                email: "Please enter valid email address."},
            password: {required: "Please enter password.", minlength: "Minimum 8 character required.",
                validpassword: "The password must contain a minimum of one lower case character," + " one upper case character, one digit and one special character"},
            confirm_password: {required: "Please retype password.", minlength: "Minimum 8 character required.", equalTo: "Passwords do not match."},
            company_name: {required: "Please enter company name."},
            title: {required: "Please enter Title."}
        },
        errorPlacement: function (error, element) {
            if (element.attr("name") == "first_name") {
                $("#first_name_error").html(error);
            }
            if (element.attr("name") == "last_name") {
                $("#last_name_error").html(error);
            }
            if (element.attr("name") == "email") {
                $("#email_error").html(error);
            }

            if (element.attr("name") == "password") {
                $("#password_error").html(error);
            }
            if (element.attr("name") == "confirm_password") {
                $("#confirm_password_error").html(error);
            }
            if (element.attr("name") == "company_name") {
                $("#company_name_error").html(error);
            }
            if (element.attr("name") == "title") {
                $("#title_error").html(error);
            }
        },

        onkeyup: false,
        submitHandler: function (form) {

            $('#SignupSubmitBtn').hide();
            $('#loginLoader').show();
            $.ajax({
                url: APP_URL + "register",
                data: new FormData($("#signup_form")[0]),
                dataType: 'json',
                async: true,
                type: 'post',
                processData: false,
                contentType: false,
                success: function (response) {

                    if (response.http_status == 200) {
                        $('#signupMessage').show().text(response.message);
                        $('#signupMessage').addClass('colorGreen').removeClass('colorRed').show().text(response.message);
                        if (response.on_boarding_status == 2) {
                            window.location = APP_URL + 'userDetails';
                        }
                        if (response.on_boarding_status == 3) {
                            window.location = APP_URL + 'userSettings';
                        }
                    } else {
                        $('#signupMessage').removeClass('colorGreen').addClass('colorRed').show().text(response.message);
                    }
                    setTimeout(function () {
                        $("#signupMessage").text('');
                    }, 5000);
                    $('#SignupSubmitBtn').show();
                    $('#loginLoader').hide();
                },
                error: function (data) {
                    var json = jQuery.parseJSON(data['responseText']);
                    $('#signupMessage').removeClass('colorGreen').addClass('colorRed').show().text(json['message']);
                    setTimeout(function () {
                        $('#signupMessage').hide();
                        closePartDeletePopUp();
                    }, 10000);
                    $('#SignupSubmitBtn').show();
                    $('#loginLoader').hide();
                }
            });

        }
    });

    //user Login
    $('#login_form').validate({
        rules: {
            user_name: {required: true, email: true},
            login_password: {required: true}
        },
        messages: {
            user_name: {required: "Please enter email address.",
                email: "Please enter valid email address."},
            login_password: {required: "Please enter password."}
        },
        errorPlacement: function (error, element) {
            if (element.attr("name") == "user_name") {
                $("#user_name_error").html(error);
            }
            if (element.attr("name") == "login_password") {
                $("#login_password_error").html(error);
            }
        },

        onkeyup: false,
        submitHandler: function (form) {

            var user_name = $('#user_name').val();
            var login_password = $('#login_password').val();
            $('#LoginSubmitBtn').hide();
            $('#loginLoader2').show();
            $.post(APP_URL + "login", {
                email: user_name, password: login_password
            },
                    function (response) {
                        $('#LoginSubmitBtn').show();
                        $('#loginLoader2').hide();
                        if (response.http_status == 200) {
                            window.location = APP_URL + "discussions";
                        } else {
                            $('#LoginSuccessMsg').removeClass('colorGreen').addClass('colorRed').text(response.message).show();
                            setTimeout(function () {
                                $('#LoginSuccessMsg').text('');
                            }, 5000);
                        }
                    }, 'json')
                    .fail(function (data) {
                        $('#LoginSubmitBtn').show();
                        $('#loginLoader2').hide();
                        var json = jQuery.parseJSON(data['responseText']);
                        $('#LoginSuccessMsg').removeClass('colorGreen').addClass('colorRed').text(json['message']).show();
                    });

        }
    });

    // Step 1 form

    $('#user_data_form').validate({
        rules: {
            name: {required: true},
            email: {required: true, email: true},
//            uploaded_image: {required: true}
        },
        messages: {
            name: {required: "Please enter name."},
            email: {required: "Please enter email address.",
                email: "Please enter valid email address."},
//            uploaded_image: {required: "Please choose profile image "}
        },
        onkeyup: false,
        submitHandler: function (form) {

            $('#submitBtnSec').hide();
            $('#loginLoader').show();
            $.ajax({
                url: APP_URL + "updateUserDetails",
                data: new FormData($("#user_data_form")[0]),
                dataType: 'json',
                async: true,
                type: 'post',
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.http_status == 200) {
                        $('#submitMessage').show().text(response.message);
                        $('#submitMessage').addClass('colorGreen').removeClass('colorRed').show().text(response.message);

                        window.location = APP_URL + 'userSettings';

                    } else {
                        $('#submitMessage').removeClass('colorGreen').addClass('colorRed').show().text(response.message);
                    }
                    setTimeout(function () {
                        $("#submitMessage").text('');
                    }, 2000);
                    $('#submitBtnSec').show();
                    $('#loginLoader').hide();
                },
                error: function (data) {
                    var json = jQuery.parseJSON(data['responseText']);
                    $('#submitMessage').removeClass('colorGreen').addClass('colorRed').show().text(json['message']);
                    setTimeout(function () {
                        $('#submitMessage').hide();
                        closePartDeletePopUp();
                    }, 10000);
                    $('#submitBtnSec').show();
                    $('#loginLoader').hide();
                }
            });

        }
    });



    $('#user_notification_settings_form').validate({
        rules: {
            notification_types: {required: true}
        },
        messages: {
            name: {required: "Please choose emial notifications type."}
        },
        onkeyup: false,
        submitHandler: function (form) {

            $('#submitBtnSec').hide();
            $('#loginLoader').show();
            $.ajax({
                url: APP_URL + "updateUserSettings",
                data: new FormData($("#user_notification_settings_form")[0]),
                dataType: 'json',
                async: true,
                type: 'post',
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.http_status == 200) {
                        $('#submitMessage').show().text(response.message);
                        $('#submitMessage').addClass('colorGreen').removeClass('colorRed').show().text(response.message);

                        window.location = APP_URL + 'codeOfConduct';

                    } else {
                        $('#submitMessage').removeClass('colorGreen').addClass('colorRed').show().text(response.message);
                    }
                    setTimeout(function () {
                        $("#submitMessage").text('');
                    }, 2000);
                    $('#submitBtnSec').show();
                    $('#loginLoader').hide();
                },
                error: function (data) {
                    var json = jQuery.parseJSON(data['responseText']);
                    $('#submitMessage').removeClass('colorGreen').addClass('colorRed').show().text(json['message']);
                    setTimeout(function () {
                        $('#submitMessage').hide();
                        closePartDeletePopUp();
                    }, 10000);
                    $('#submitBtnSec').show();
                    $('#loginLoader').hide();
                }
            });

        }
    });


    //code_conduct_form
    $("#code_conduct_btn").on('click', function () {
        var element = document.getElementById('code_tc');

        if (element.scrollHeight - Math.round(element.scrollTop) != element.clientHeight)
        {
            $('#submitMessage').removeClass('colorGreen').addClass('colorRed').show().text('Please read Code of Conduct.');
            return false;
        }
        if (element.scrollHeight - Math.round(element.scrollTop) === element.clientHeight)
        {
            $('#submitMessage').hide();
             
        }

        $('#code_conduct_form').validate({
            rules: {
                code_of_conduct: {required: true}
            },
            messages: {
                code_of_conduct: {required: "Please agree to the Code of Conduct."}
            },
            errorPlacement: function (error, element) {
                if (element.attr("name") == "code_of_conduct")
                    $("#conde_conduct_error").html(error);
            },
            onkeyup: false,
            submitHandler: function (form) {

                $('#submitBtnSec').hide();
                $('#loginLoader').show();
                $.ajax({
                    url: APP_URL + "updateCodeConductStatus",
                    data: new FormData($("#code_conduct_form")[0]),
                    dataType: 'json',
                    async: true,
                    type: 'post',
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response.http_status == 200) {
                            $('#submitMessage').show().text(response.message);
                            $('#submitMessage').addClass('colorGreen').removeClass('colorRed').show().text(response.message);

                            window.location = APP_URL + 'signupCompleted';

                        } else {
                            $('#submitMessage').removeClass('colorGreen').addClass('colorRed').show().text(response.message);
                        }
                        setTimeout(function () {
                            $("#submitMessage").text('');
                        }, 2000);
                        $('#submitBtnSec').show();
                        $('#loginLoader').hide();
                    },
                    error: function (data) {
                        var json = jQuery.parseJSON(data['responseText']);
                        $('#submitMessage').removeClass('colorGreen').addClass('colorRed').show().text(json['message']);
                        setTimeout(function () {
                            $('#submitMessage').hide();
                            closePartDeletePopUp();
                        }, 10000);
                        $('#submitBtnSec').show();
                        $('#loginLoader').hide();
                    }
                });

            }
        });
    });

    // forgot password form
    $('#forgot_password_form').validate({
        rules: {
            forgot_password_email: {required: true, email: true}
        },
        messages: {
            forgot_password_email: {required: "Please enter email address.",
                email: "Please enter valid email address."}
        },
        onkeyup: false,
        submitHandler: function (form) {
            var email = $('#forgot_password_email').val();
            $('#ForgotPasswordSubmitBtn').hide();
            $('#ForgotPasswordLoader').show();
            $.post(APP_URL + "forgotPasswordRequest", {
                email: email
            },
                    function (response) {
                        $('#ForgotPasswordSubmitBtn').show();
                        $('#ForgotPasswordLoader').hide();
                        if (response.http_status == 200) {
                            $('#ForgotPasswordSuccessMsg').removeClass('colorRed').addClass('colorGreen').text(response.message).show();
                            setTimeout(function () {
                                window.location = APP_URL + "/";

                            }, 2000);
                        } else {
                            $('#ForgotPasswordSuccessMsg').removeClass('colorGreen').addClass('colorRed').text(response.message).show();
                        }
                    }, 'json')
                    .fail(function (data) {
                        $('#ForgotPasswordSubmitBtn').show();
                        $('#ForgotPasswordLoader').hide();
                        var json = jQuery.parseJSON(data['responseText']);
                        $('#ForgotPasswordSuccessMsg').removeClass('colorGreen').addClass('colorRed').text(json['message']).show();
                        setTimeout(function () {
                            $('#ForgotPasswordSuccessMsg').text('');

                        }, 10000);


                    });

        }
    });

//------------------------------------------------------------------------------
// Reset password form
    $('#reset_password_form').validate({
        rules: {
            new_password: {required: true, minlength: 8, validpassword: true}
        },
        messages: {
            new_password: {required: "Please enter password.",
                minlength: "Password minimum 8 characters.",
                validpassword: "The password must contain a minimum of one lower case character," + " one upper case character, one digit and one special character"}
        },
        onkeyup: false,
        submitHandler: function (form) {
            var new_password = $('#new_password').val();
            var resetToken = $('#resetToken').val();
            $('#resetPasswordSubmitBtn').hide();
            $('#resetPasswordLoader').show();
            $.post(APP_URL + "resetPassword", {
                new_password: new_password, resetToken: resetToken
            },
                    function (response) {
                        $('#resetPasswordSubmitBtn').show();
                        $('#resetPasswordLoader').hide();
                        if (response.http_status == 200) {
                            $('#resetPasswordSuccessMsg').removeClass('colorRed').addClass('colorGreen').text(response.message).show();
                            setTimeout(function () {
                                window.location = APP_URL + "/";

                            }, 2000);
                        } else {
                            $('#resetPasswordSuccessMsg').removeClass('colorGreen').addClass('colorRed').text(response.message).show();

                        }
                    }, 'json')
                    .fail(function (data) {
                        $('#resetPasswordSubmitBtn').show();
                        $('#resetPasswordLoader').hide();
                        var json = jQuery.parseJSON(data['responseText']);
                        $('#resetPasswordSuccessMsg').removeClass('colorGreen').addClass('colorRed').text(json['message']).show();
                        setTimeout(function () {
                            $('#resetPasswordSuccessMsg').text('');

                        }, 10000);
                    });

        }
    });

});

function showImageUpload() {
    $('#info_card').hide();
    $('#profile_image_upload').show();
}

    