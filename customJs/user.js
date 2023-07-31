$(document).ready(function () {

    $("#allUsers").click(function () {
        var page = 1;
        var sort_type = $('#hidden_sort_type').val();
        var sort_column = $("#hidden_sort_column").val();
        $("#account_type").val('all');
        var query = 'all';
        window.location.hash = '#allUsersCont';
        $("#alUsersDownload").show();
        $("#account_type").html('Account Activation Date');
        getUsers(page, sort_type, sort_column, query);
    });

    $("#newAccountRequests").click(function () {
        var page = 1;
        var sort_type = $('#hidden_sort_type').val();
        $("#account_type").val('new_accounts');
        var sort_column = $("#hidden_sort_column").val();
        var query = 'new_accounts';
        window.location.hash = '#newAccountRequests';
        $("#alUsersDownload").hide();
        $("#account_type").html('Activate Account');
        getUsers(page, sort_type, sort_column, query);
    });

    $("#deactivatedAccounts").click(function () {
        var page = 1;
        var sort_type = $('#hidden_sort_type').val();
        $("#account_type").val('deactivated_accounts');
        var sort_column = $("#hidden_sort_column").val();
        var query = 'deactivated_accounts';
        window.location.hash = '#deactivatedAccounts';
        $("#alUsersDownload").hide();
        $("#account_type").html('Reason for Deactivation');
        getUsers(page, sort_type, sort_column, query);
    });

    //user list pagination code start
    $(document).on('click', '#userListPagination a', function (event) {
        event.preventDefault();
        $('li').removeClass('active');
        $(this).parent('li').addClass('active');
        var page = $(this).attr('href').split('page=')[1];
        $('#hidden_page').val(page);
        var sort_type = $('#hidden_sort_type').val();
        var sort_column = $("#hidden_sort_column").val();
        var type = $("#account_type").val();

        getUsers(page, sort_type, sort_column, type);
    });
    //user list pagination code end

    //user list search code start
    $(document).on('keyup', '#serach', function () {
        var query = $('#serach').val();
        var column_name = $('#hidden_column_name').val();
        var sort_type = $('#hidden_sort_type').val();
        var page = 1;

        getUsers(page, sort_type, column_name);
    });
    //user list search code end

    //user list sorting code start
    $(document).on('click', '.sorting', function () {
        var column_name = $(this).data('column_name');
        var order_type = $(this).data('sorting_type');
        $(".sorting").removeClass("active");
        var reverse_order = 'desc';
        if (order_type == 'asc') {
            $(this).data('sorting_type', 'desc');
            reverse_order = 'desc';
            $('#hidden_sort_type').val('desc');
            $('#' + column_name).addClass('active');
        }
        if (order_type == 'desc') {
            $(this).data('sorting_type', 'asc');
            reverse_order = 'asc';
            $('#hidden_sort_type').val('asc');

        }

        $('#hidden_sort_column').val(column_name);

        var page = $('#hidden_page').val();
        var type = $("#account_type").val();
        getUsers(page, reverse_order, column_name, type);
    });
    //user list sorting code end



    //Create user functionality ajax call start
    $.validator.addMethod("formatPhoneNumber", function (value, element) {
        return this.optional(element) || value === "NA" ||
                value.match(/^(?=.*[0-9])[- +()0-9]+$/);
    }, "Please enter a valid number, or 'NA'");


    //Create user functionality ajax call end

    $("#userEditBtn").click(function () {
        $(":text,:checkbox,:password,:file,select,textarea").each(function () {
            $(this).attr("disabled", false);
        });
        $("body #user_role").prop("disabled", true);
        $("select").material_select();
        $('#userEditBtn').hide();
        $('#userSaveBtn').show();
        $('#userSaveBtn').val("Save");
        $('#userPopupTitle').text("Edit User");
    });

    //user reset password link to user ajax call start
    $('#user_reset_password_request_form').validate({
        submitHandler: function (form) {
            $('#passwordSubmitBtn').hide();
            $('#passwordSubmitLoader').show();
            $.ajax({
                url: APP_URL + "userResetPasswordRequest",
                data: new FormData($("#user_reset_password_request_form")[0]),
                dataType: 'json',
                async: true,
                type: 'post',
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.http_status == 200) {
                        $('#passwordMessage').show().text(response.message);
                        $('#passwordMessage').addClass('colorGreen').removeClass('colorRed').show().text(response.message);
                    } else {
                        $('#passwordMessage').removeClass('colorGreen').addClass('colorRed').show().text(response.message);
                    }
                    setTimeout(function () {
                        closeResetPassPopUp();
                        $("#passwordMessage").text('');
                    }, 2000);
                    $('#passwordSubmitBtn').show();
                    $('#passwordSubmitLoader').hide();
                },
                error: function (data) {
                    var json = jQuery.parseJSON(data['responseText']);
                    $('#passwordMessage').removeClass('colorGreen').addClass('colorRed').show().text(json['message']);
                    setTimeout(function () {
                        $('#passwordMessage').hide();
                        closePartDeletePopUp();
                    }, 10000);
                    $('#passwordSubmitBtn').show();
                    $('#passwordSubmitLoader').hide();
                }
            });
        }
    });
    //send reset password link to user ajax call end
    //------------------------------------------------------------------------------
    // User Reset password form
    $('#user_reset_password_form').validate({
        rules: {
            new_password: {required: true, minlength: 8}
        },
        messages: {
            new_password: {
                required: "Please enter password.",
                minlength: "Password minimum 8 characters."
            }
        },
        onkeyup: false,
        submitHandler: function (form) {
            var new_password = $('#new_password').val();
            var resetToken = $('#resetToken').val();
            $('#resetPasswordSubmitBtn').hide();
            $('#resetPasswordLoader').show();
            $.post(APP_URL + "userResetPassword", {
                new_password: new_password,
                resetToken: resetToken
            },
                    function (response) {
                        $('#resetPasswordSubmitBtn').show();
                        $('#resetPasswordLoader').hide();
                        if (response.http_status == 200) {
                            $('#resetPasswordSuccessMsg').removeClass('colorRed').addClass('colorGreen').text(response.message).show();
                            setTimeout(function () {
                                $('#resetPasswordSuccessMsg').text('');
                                $('#new_password').val('');
                                if (response.user_role == 3 || response.user_role == 4) {
                                    window.location = APP_URL + "/";
                                }
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
    // User Reset password form
    //delete user functionality ajax call start
    $('#delete_user_form').validate({
        submitHandler: function (form) {
            $('#deleteUserSubmitBtn').hide();
            $('#deleteUserLoader').show();
            $.ajax({
                url: APP_URL + "deleteUser",
                data: new FormData($("#delete_user_form")[0]),
                dataType: 'json',
                async: true,
                type: 'post',
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.http_status == 200) {
                        $('#deleteUserMessage').addClass('colorGreen').removeClass('colorRed').show().text(response.message);
                        $('#userinformation_' + response.user_id).slideUp('slow').remove();
                        if (response.user_count == 0) {
                            $('#userListData').append(response.data);
                        }
                        var query = $('#serach').val();
                        var column_name = $('#hidden_column_name').val();
                        var sort_type = $('#hidden_sort_type').val();
                        var page = $('#hidden_page').val();
                        var filter_value = $('#hidden_filter_value').val();
                        getUsers(page, sort_type, column_name, query, filter_value);
                    } else {
                        $('#deleteUserMessage').removeClass('colorGreen').addClass('colorRed').show().text(response.message);
                    }
                    setTimeout(function () {
                        closUsereDeletePopUp();
                        $("#deleteUserMessage").text('');
                    }, 2000);
                    $('#deleteUserSubmitBtn').show();
                    $('#deleteUserLoader').hide();
                },
                error: function (data) {
                    var json = jQuery.parseJSON(data['responseText']);
                    if (json['http_status'] == 401) {
                        window.location = APP_URL + "/";
                    }
                    $('#deleteUserMessage').removeClass('colorGreen').addClass('colorRed').show().text(json['message']);
                    setTimeout(function () {
                        $('#deleteUserMessage').hide();
                        closePartDeletePopUp();
                    }, 10000);
                    $('#deleteUserSubmitBtn').show();
                    $('#deleteUserLoader').hide();
                }
            });
        }
    });
    //delete user functionality ajax call end



$('#deactivate_form').validate({
        submitHandler: function (form) {
            $('#deactivationSubmitBtn').hide();
            $('#deactivatingLoader').show();
            $.ajax({
                url: APP_URL + "deactivateUser",
                data: new FormData($("#deactivate_form")[0]),
                dataType: 'json',
                async: true,
                type: 'post',
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.http_status == 200) {
                         $("#remove_notify_msg").hide();
                            $("#discussion_heading_text2").hide();
                            $("#deactivated_success").show();
                            $("#remove_notify_success_msg").show();
                            $('#deactivationSubmitBtn').hide();
                            $("#deactivate_form").trigger("reset");
                        var page = 1;
                        var sort_type = $('#hidden_sort_type').val();
                        var sort_column = $("#hidden_sort_column").val();
                        $("#account_type").val('all');
                        var query = 'all';
                        window.location.hash = '#allUsersCont';
                        $("#alUsersDownload").show();
                        $("#account_type").html('Account Activation Date');
                        getUsers(page, sort_type, sort_column, query);
                    } else {
                        $('#deactivation_msg').removeClass('colorGreen').addClass('colorRed').show().text(response.message);
                    }
                      
                    $('#deactivatingLoader').hide();
                },
                error: function (data) {
                    var json = jQuery.parseJSON(data['responseText']);
                    if (json['http_status'] == 401) {
                        window.location = APP_URL + "/";
                    }
                    $('#deactivation_msg').removeClass('colorGreen').addClass('colorRed').show().text(json['message']);
                    
                    $('#deactivationSubmitBtn').show();
                    $('#deactivatingLoader').hide();
                }
            });
        }
    });

    $("#usersExportBtn").click(function () {
        $("#userListLoader").show();
        $.ajax({
            url: APP_URL + 'exportUsers',
            dataType: 'json',
            data: null,
            async: true,
            type: 'post',
            processData: false,
            contentType: false,
            success: function (response) {

                $("#userListLoader").hide();
                if (response.http_status == 200) {
                    $('#users_export_message').addClass('success').removeClass('error').show().text(response.message);

                    if (navigator.userAgent.indexOf("Firefox") > 0) {
                        window.location.href = APP_URL + response.download_url;
                    } else
                    {
                        var url = APP_URL + response.download_url;
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = response.file_name;
                        a.click();
                    }
                    setTimeout(function () {
                        removeFile(response.download_url);
                    }, 1000);
                    setTimeout(function () {
                        $('#users_export_message').text();
                    }, 1000);
                } else {
                    $('#users_export_message').addClass('error').removeClass('success').show().text(response.message);
                }
                setTimeout(function () {
                    $('#transfer_export_message').hide().removeClass('error');
                }, 2000);
                if (response.http_status == 440) {
                    window.location = APP_URL + "/";
                }
            },
            error: function (data) {
                $("#userListLoader").hide();
                var json = jQuery.parseJSON(data['responseText'])
                if (json['http_status'] == 440) {
                    window.location = APP_URL + "/";
                }
                $('#users_export_message').addClass('error').removeClass('success').show().text(json['message']);
                setTimeout(function () {
                    $('#users_export_message').hide().removeClass('error');
                }, 10000);
            }

        });
    });



});



function updateStatus(user_id, email) {
    $("#maskMenuBg").addClass("menuMaskOn");
    $("body").addClass("hiddenBody");
    $("#userListLoader").show();
    $.ajax({
        url: APP_URL + 'updateUserStatus?user_id=' + user_id,
        type: 'get',
        dataType: 'json',
        async: true,
        processData: false,
        contentType: false,
        success: function (response) {
            $("#userListLoader").hide();
            if (response.http_status == 200) {
                $("#account_activated").show();
                $("#activated_email").empty().html(email);
                var page = 1;
                var sort_type = $('#hidden_sort_type').val();
                $("#account_type").val('new_accounts');
                var sort_column = $("#hidden_sort_column").val();
                var query = 'new_accounts';
                window.location.hash = '#newAccountRequests';
                $("#alUsersDownload").hide();
//                $("#account_activated").hide();
                getUsers(page, sort_type, sort_column, query);
            } else if (response.http_status == 201) {
                $('#users_export_message').addClass('error').removeClass('success').show().text(response.message);
            }
            if (response.http_status == 440) {
                window.location = APP_URL + "/";
            }
        },
        error: function (data) {
            $("#userListLoader").hide();
            var json = jQuery.parseJSON(data['responseText'])
            if (json['http_status'] == 440) {
                window.location = APP_URL + "/";
            }
            $('#users_export_message').addClass('error').show().text(json['message']);
            setTimeout(function () {
                $('#users_export_message').hide().removeClass('error');
            }, 10000);
        }

    });
}

function closePopup() {
    $("#account_activated").hide();
    $("#maskMenuBg").removeClass("menuMaskOn");
    $("body").removeClass("hiddenBody");
}

$('#phone_number').on('keyup focus', function () {
    this.value = this.value.replace(/\D/g, '');
    var format = $(this).val().split("-").join(""); // remove hyphens
    format = format.replace(/(\d\d\d)(\d\d\d)(\d\d\d\d)/, "$1-$2-$3");
    $(this).val(format);
});

function getTimeZoneHours() {
    var timezone_offset_min = new Date().getTimezoneOffset(),
            offset_hrs = parseInt(Math.abs(timezone_offset_min / 60)),
            offset_min = Math.abs(timezone_offset_min % 60),
            timezone_standard;
    if (offset_hrs < 10)
        offset_hrs = '0' + offset_hrs;

    if (offset_min < 10)
        offset_min = '0' + offset_min;

    // Add an opposite sign to the offset
    // If offset is 0, it means timezone is UTC
    if (timezone_offset_min < 0)
        timezone_standard = '+' + offset_hrs + ':' + offset_min;
    else if (timezone_offset_min > 0)
        timezone_standard = '-' + offset_hrs + ':' + offset_min;
    else if (timezone_offset_min == 0)
        timezone_standard = 'Z';

    // Timezone difference in hours and minutes
    // String such as +5:30 or -6:00 or Z
    $('#timezone_hours').val(timezone_standard);
}
//ajax call to get the list of users based on all conditions...code start
function getUsers(page, sort_type, sort_by, type) {
    $("#userListLoader").show();

    $.ajax({
        url: APP_URL + 'users?page=' + page + "&sortby=" + sort_by + "&sorttype=" + sort_type + "&type=" + type,
        type: 'get',
        dataType: 'json',
        async: true,
        processData: false,
        contentType: false,
        success: function (response) {
            $("#userListLoader").hide();
            $('#users_export_message').empty().text();
            if (response.http_status == 200) {
                $("#allUsersCont").show();
                $("#userListData").empty().html(response.html);

            } else if (response.http_status == 201) {
                $("#userListData").empty().html(response.html);
            }
            if (response.http_status == 440) {
                window.location = APP_URL + "/";
            }


        },
        error: function (data) {
            $("#userListLoader").hide();
            var json = jQuery.parseJSON(data['responseText'])
            if (json['http_status'] == 440) {
                window.location = APP_URL + "/";
            }
            $('#userListErrorMessage').addClass('error').show().text(json['message']);
            setTimeout(function () {
                $('#userListErrorMessage').hide().removeClass('error');

            }, 10000);

        }

    });
}
//ajax call to get the list of users based on all conditions...code end
//user details section
function getUserDetails(user_id, edit_type) {
    window.location = APP_URL + "userRedirect/" + user_id + "/" + edit_type;
}


//remove file from local storage method start
function removeFile(file) {
    var formData = new FormData();
    formData.append('file_path', file);
    $.ajax({
        url: APP_URL + 'removeFile',
        data: formData,
        type: 'post',
        processData: false,
        contentType: false,
        success: function (response) {
            return true;
        },
        error: function (data) {
            return true;
        }
    });
}
//remove file from local storage method end
function openDeactivationPopup(user_id, email) {

    $("#user_id").val(user_id);
    $("#user_email").html(email);
    $("#remove_email_success").html(email);
    $("#deactivated_success").hide();
    $("#remove_notify_success_msg").hide();
    $("#remove_notify_msg").show();
    $("#deactivationSubmitBtn").show();
    $("#discussion_heading_text2").show();
    $('#deactivate_content').show();
    $("#maskMenuBg").addClass("menuMaskOn");
    $("body").addClass("hiddenBody");


}
;

function closeDeactivatePopUp() {
    $('#deactivate_content').hide();
    $("#maskMenuBg").removeClass("menuMaskOn");
    $("body").removeClass("hiddenBody");
    var page = 1;
    var sort_type = $('#hidden_sort_type').val();
    var sort_column = $("#hidden_sort_column").val();
    var query = 'all';
    window.location.hash = '#allUsersCont';
    getUsers(page, sort_type, sort_column, query)
}


function deactivateUser() {
    $("#maskMenuBg").addClass("menuMaskOn");
    $("body").addClass("hiddenBody");
    $("#userListLoader").show();
    $.ajax({
        url: APP_URL + 'updateUserStatus?user_id=' + user_id,
        type: 'get',
        dataType: 'json',
        async: true,
        processData: false,
        contentType: false,
        success: function (response) {
            $("#userListLoader").hide();
            if (response.http_status == 200) {
                $("#account_activated").show();
                $("#activated_email").empty().html(email);
                var page = 1;
                var sort_type = $('#hidden_sort_type').val();
                $("#account_type").val('new_accounts');
                var sort_column = $("#hidden_sort_column").val();
                var query = 'new_accounts';
                window.location.hash = '#newAccountRequests';
                $("#alUsersDownload").hide();
//                $("#account_activated").hide();
                getUsers(page, sort_type, sort_column, query)
            } else if (response.http_status == 201) {
                $('#users_export_message').addClass('error').removeClass('success').show().text(response.message);
            }
            if (response.http_status == 440) {
                window.location = APP_URL + "/";
            }
        },
        error: function (data) {
            $("#userListLoader").hide();
            var json = jQuery.parseJSON(data['responseText'])
            if (json['http_status'] == 440) {
                window.location = APP_URL + "/";
            }
            $('#users_export_message').addClass('error').show().text(json['message']);
            setTimeout(function () {
                $('#users_export_message').hide().removeClass('error');
            }, 10000);
        }

    });
}