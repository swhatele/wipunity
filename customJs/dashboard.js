
//Dashboard javascript functions file 

$(document).ready(function () {
    var page = 1;
    var max_page = 1;
    var filter_page = 1;
    var filter_max_page = 1;

    $("#launch_discussion").on('click', function () {
        $("#launch_conf").removeClass('hide');
        $("#popup_mask").removeClass('hide');
    });
    $("#launch_discussion_yes").on('click', function () {
        $("#launch_conf").addClass('hide');
        selectBasin(0);
        $(".tags-list").removeClass("tag-active");
        $("#launch_discussion_data").removeClass('hide');
        $("#launch_discussion_form").trigger("reset");
        showUploadPicture();
    });
    $("#launch_discussion_no").on('click', function () {
        $("#launch_conf").addClass('hide');
        $("#popup_mask").addClass('hide');
    });
    //  search code start
    $(document).on('keyup', '#search_text', function () {
        var query = $('#search_text').val();
        if (query.length == 0) {
            if (window.location.href.indexOf("discussion") > -1) {
                location.reload();
            }
        }
        console.log(query);
        $.ajax({
            url: APP_URL + "searchDiscussions",
            data: {query: query, type: 1},
            type: 'get',
            dataType: 'json',
//            processData: false,
//            contentType: false,
            success: function (response) {

                if (response.http_status == 200) {
                    $("#dashboard_bubbles").empty();
                    $("#dashboard_bubbles").html(response.html);
                    page = response.currentPage;
                    max_page = response.lastPage;

                }
                if (response.http_status == 203) {
                    $("#fiters_error").addClass('colorRed').text(response.message);
                }
                if (response.http_status == 440) {
                    window.location = APP_URL + "/";
                }


            },
            error: function (data) {
                var json = jQuery.parseJSON(data['responseText'])
                if (json['http_status'] == 440) {
                    window.location = APP_URL + "/";
                }

                $("#fiters_error").addClass('colorRed').text(json['message']);
                setTimeout(function () {
                    $("#fiters_error").removeClass('colorRed');
                    $("#fiters_error").hide();
                }, 10000);
            }

        });
    });

    $(document).on('keyup', '#search_text2', function () {
        var query = $('#search_text2').val();
        if (query.length == 0) {
            if (window.location.href.indexOf("discussion") > -1) {
                location.reload();
            }
        }
        $.ajax({
            url: APP_URL + "searchDiscussions",
            data: {query: query, type: 1},
            type: 'get',
            dataType: 'json',
//            processData: false,
//            contentType: false,
            success: function (response) {

                if (response.http_status == 200) {
                    $("#dashboard_bubbles").empty();
                    $("#dashboard_bubbles").html(response.html);
                    page = response.currentPage;
                    max_page = response.lastPage;

                }
                if (response.http_status == 203) {
                    $("#fiters_error").addClass('colorRed').text(response.message);
                }
                if (response.http_status == 440) {
                    window.location = APP_URL + "/";
                }


            },
            error: function (data) {
                var json = jQuery.parseJSON(data['responseText'])
                if (json['http_status'] == 440) {
                    window.location = APP_URL + "/";
                }

                $("#fiters_error").addClass('colorRed').text(json['message']);
                setTimeout(function () {
                    $("#fiters_error").removeClass('colorRed');
                    $("#fiters_error").hide();
                }, 10000);
            }

        });
    });
    //  search code end

    $("#loadMoreReplies").on('click', function () {
        var discussion_id = $("#discussion_id").val();
        var last_reply_id = $("#last_reply_id").val();
        var page = $("#hidden_page").val();
        var load_more = $("#hidden_load_more").val();
        $.ajax({
            url: APP_URL + "loadMoreReplies?page=" + page + "&discussion_id=" + discussion_id + "&last_reply_id=" + last_reply_id + '&load_more=' + load_more,
            type: 'get',
            dataType: 'json',
            data: {discussion_id: discussion_id, last_reply_id: last_reply_id},
            success: function (response) {
                if (response.http_status == 200) {
                    $("#replies_list_entry").after(response.html);
                    page++;
                    $("#hidden_page").val(page);
                    if (response.replies_count == 1) {
                        $("#loadMoreReplies").hide();
                    }
                    if (load_more == 1) {
                        $("#hidden_load_more").val(2);
                    } else {
                        $("#hidden_load_more").val(1);
                    }

                }
                if (response.http_status == 201) {
                    $("#loadMoreReplies").hide();
                }
                if (response.http_status == 203) {
                    $("#replies_list_entry").addClass('colorRed').text(response.message);
                }
                if (response.http_status == 440) {
                    window.location = APP_URL + "/";
                }


            },
            error: function (data) {
                var json = jQuery.parseJSON(data['responseText'])
                if (json['http_status'] == 440) {
                    window.location = APP_URL + "/";
                }

                $("#replies_list_entry").addClass('colorRed').text(json['message']);
                setTimeout(function () {
                    $("#replies_list_entry").removeClass('colorRed');
                    $("#replies_list_entry").hide();
                }, 10000);
            }

        });
    });
    $('#add_discussion').on('click', function () {

        $('#launch_discussion_form').validate({
            ignore: [],
            rules: {

                topic: {
                    required: true,
                    rangelength: [5, 1000],
                },
                basin_id: {
                    required: true,
                    min: 1,
                    number: true,
                },
                tag_ids: {
                    required: true,
                },
                upload_image: {
                    required: false,
//                    extension: "jpeg|jpg|png|tiff",
                    filesize: 5000000,
                },
            },
            messages: {
                topic: {required: "Please enter discussion.",
                    rangelength: "Please enter 5-1000 characters."},
                basin_id: {required: "Please select basin.",
                    min: "Please select basin.", number: "Please select basin."},
                tag_ids: {required: "Please select one or more tags."},
                upload_image: {
                    filesize: " file size must be less than 50 KB.",
                    //                    extension: "Please upload .jpg or .png or tiff file ."
                },
            },
            onkeyup: false,
            submitHandler: function (form) {
                $("#add_discussion_loader").show();
                $("#add_discussion").hide();
                var formData = new FormData($("#launch_discussion_form")[0]);
                $.ajax({
                    url: APP_URL + "postDiscussion",
                    data: formData,
                    dataType: 'json',
                    async: true,
                    type: 'post',
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        $("#add_discussion_loader").hide();
                        $("#add_discussion").show();
                        if (response.http_status == 200) {
                            $("#launch_discussion_data").addClass('hide');
                            $("#discussion_added").removeClass('hide');
                            $("#launch_discussion_form").trigger("reset");
                        }
                        if (response.http_status == 203) {
                            $("#post_meeting_error").addClass('colorRed').text(response.message);
                        }
                        if (response.http_status == 440) {
                            window.location = APP_URL + "/";
                        }


                    },
                    error: function (data) {
                        $("#add_discussion_loader").hide();
                        $("#add_discussion").show();
                        var json = jQuery.parseJSON(data['responseText'])
                        if (json['http_status'] == 440) {
                            window.location = APP_URL + "/";
                        }

                        $("#post_meeting_error").addClass('colorRed').text(json['message']);
                        setTimeout(function () {
                            $("#post_meeting_error").removeClass('colorRed');
                            $("#post_meeting_error").hide();
                        }, 10000);
                    }

                });
            }
        });
    });
    $('#saveMeeting').on('click', function () {

        $('#meeting_form').validate({
            rules: {

                street_1: {
                    multipleFieldValidator: true,
                },
                city: {
                    multipleFieldValidator: true,
                },
                state: {
                    multipleFieldValidator: true,
                },
                zipcode: {
                    multipleFieldValidator: true,
                    number: true,
                    minlength: 5
                },
                venue_name: {
                    multipleFieldValidator: true,
                },
                meeting_date: {
                    required: true,
//                lessThan:"#end_date"
                },
                start_time: {required: true, },
                end_time: {
                    required: true,
                    timeValidator: "#start_time"
                },
                purpose_of_meeting: {required: true, },
            },
            messages: {
                start_time: {required: "Please select start time"},
                end_time: {required: "Please select end time"},
                purpose_of_meeting: {required: "Please enter purpose of meeting"},
            },
            errorPlacement: function (error, element) {
                //Custom position: first name
                if (element.attr("name") == "meeting_date") {

                    $("#date_error").html(error);
                }
                //Custom position: second name
                else if (element.attr("name") == "start_time") {
                    $("#start_time_error").html(error);
                } else if (element.attr("name") == "end_time") {
                    $("#end_time_error").html(error);
                }
                // Default position: if no match is met (other fields)
                else {
                    error.insertAfter(element);
                }
            },
            onkeyup: false,
            submitHandler: function (form) {
                $("#add_meeting_loader").show();
                $("#saveMeeting").hide();
                var formData = new FormData($("#meeting_form")[0]);
                formData.append('discussion_id', $("#discussion_id").val());
                $.ajax({
                    url: APP_URL + "postMeeting",
                    data: formData,
                    dataType: 'json',
                    async: true,
                    type: 'post',
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response.http_status == 200) {

                            $("#schedule_meeting").addClass('hide').hide();
                            $("#popup_mask").addClass('hide');
                            $("#meeting_list_entry").after(response.html);
                            $("#meeting_form").trigger("reset");
                            $("#meeting_id").val('-1');
                        }
                        if (response.http_status == 203) {
                            $("#post_meeting_error").addClass('colorRed').text(response.message);
                        }
                        if (response.http_status == 440) {
                            window.location = APP_URL + "/";
                        }
                        $("#add_meeting_loader").hide();
                        $("#saveMeeting").show();
                    },
                    error: function (data) {
                        var json = jQuery.parseJSON(data['responseText'])
                        if (json['http_status'] == 440) {
                            window.location = APP_URL + "/";
                        }

                        $("#post_meeting_error").addClass('colorRed').text(json['message']);
                        setTimeout(function () {
                            $("#post_meeting_error").removeClass('colorRed');
                            $("#post_meeting_error").hide();
                            $("#add_meeting_loader").hide();
                            $("#saveMeeting").show();
                        }, 10000);
                    }

                });
            }
        });
    });
    $("#confirm_attendance").on('click', function () {

        $('#attend_meeting_form').validate({
            rules: {

                meeting_attend_email: {
                    email: true,
                    required: true,
                }
            },
            messages: {

                email: {required: "Please enter email address",
                    email: "Please enter valid email address."},
            },
            onkeyup: false,
            submitHandler: function (form) {
                var formData = new FormData($("#attend_meeting_form")[0]);
                formData.append('meeting_id', $("#show_meeting_id").val());
                $.ajax({
                    url: APP_URL + "confirm_meeting_attendance",
                    data: formData,
                    dataType: 'json',
                    async: true,
                    type: 'post',
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response.http_status == 200) {

                            $("#sch_alert_attend").addClass('hide');
                            $("#sch_alert_not_attend").removeClass('hide');
                            $("#attend_meeting_form").trigger("reset");
                            $("#schedule_confirm").addClass('hide');
                            $("#schedule_done").removeClass('hide');
                        }
                        if (response.http_status == 203) {
                            $("#post_meeting_error").addClass('colorRed').text(response.message);
                        }
                        if (response.http_status == 440) {
                            window.location = APP_URL + "/";
                        }


                    },
                    error: function (data) {
                        var json = jQuery.parseJSON(data['responseText'])
                        if (json['http_status'] == 440) {
                            window.location = APP_URL + "/";
                        }

                        $("#post_meeting_error").addClass('colorRed').text(json['message']);
                        setTimeout(function () {
                            $("#post_meeting_error").removeClass('colorRed');
                            $("#post_meeting_error").hide();
                        }, 10000);
                    }

                });
            }
        });
    });
    $("#not_attending_to_meeting").on('click', function () {

        $('#not_attend_meeting_form').validate({
            rules: {

                not_attend_meeting_reason: {
                    email: true,
                }
            },
            messages: {

                not_attend_meeting_reason: {required: "Please choose reasons.", },
            },
            onkeyup: false,
            submitHandler: function (form) {
                var formData = new FormData($("#not_attend_meeting_form")[0]);
                formData.append('meeting_id', $("#show_meeting_id").val());
                $.ajax({
                    url: APP_URL + "notAttendingToMeeting",
                    data: formData,
                    dataType: 'json',
                    async: true,
                    type: 'post',
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response.http_status == 200) {
                            $("#not_attend_meeting_form").trigger("reset");
                            $("#schedule_checkbox").addClass('hide');
                            $("#schedule_endbox").removeClass('hide');
                        }
                        if (response.http_status == 203) {
                            $("#post_meeting_error").addClass('colorRed').text(response.message);
                        }
                        if (response.http_status == 440) {
                            window.location = APP_URL + "/";
                        }


                    },
                    error: function (data) {
                        var json = jQuery.parseJSON(data['responseText'])
                        if (json['http_status'] == 440) {
                            window.location = APP_URL + "/";
                        }

                        $("#post_meeting_error").addClass('colorRed').text(json['message']);
                        setTimeout(function () {
                            $("#post_meeting_error").removeClass('colorRed');
                            $("#post_meeting_error").hide();
                        }, 10000);
                    }

                });
            }
        });
    });
    $('#update_discussion').on('click', function () {

        $('#edit_discussion_form').validate({
            ignore: [],
            rules: {

                topic: {
                    required: true,
                    rangelength: [5, 1000],
                },
//                basin_id: {
//                    required: true,
//                    min: 1,
//                    number: true,
//                },
                tag_ids: {
                    required: true,
                },
//                upload_image: {
//                    required: false,
////                    extension: "jpeg|jpg|png|tiff",
//                    filesize: 5000000,
//                },
            },
            messages: {
                topic: {required: "Please enter discussion.",
                    rangelength: "Please enter 5-1000 characters."},
                tag_ids: {required: "Please select one or more tags."},
            },
            onkeyup: false,
            submitHandler: function (form) {
                $("#add_discussion_loader").show();
                $("#update_discussion").hide();
                var formData = new FormData($("#edit_discussion_form")[0]);
                $.ajax({
                    url: APP_URL + "updateDiscussion",
                    data: formData,
                    dataType: 'json',
                    async: true,
                    type: 'post',
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        $("#add_discussion_loader").hide();
                        $("#update_discussion").show();
                        if (response.http_status == 200) {
                            $("#launch_discussion_data").addClass('hide');
                            location.reload();
                        }
                        if (response.http_status == 203) {
                            $("#post_meeting_error").addClass('colorRed').text(response.message);
                        }
                        if (response.http_status == 440) {
                            window.location = APP_URL + "/";
                        }


                    },
                    error: function (data) {
                        $("#add_discussion_loader").hide();
                        $("#add_discussion").show();
                        var json = jQuery.parseJSON(data['responseText'])
                        if (json['http_status'] == 440) {
                            window.location = APP_URL + "/";
                        }

                        $("#post_meeting_error").addClass('colorRed').text(json['message']);
                        setTimeout(function () {
                            $("#post_meeting_error").removeClass('colorRed');
                            $("#post_meeting_error").hide();
                        }, 10000);
                    }

                });
            }
        });
    });


    if ((window.location.href).indexOf("discussions") > -1) {
        $(window).scroll(function () {



            if ($(window).scrollTop() == $(document).height() - $(window).height()) {

                if (filter_tags.length > 0 || filter_basins.length > 0) {
                    discussionFilterLoadmore();
                    return false;
                }
//                console.log('max_page');
//                console.log(page);
//                console.log(max_page);

                if (page == 1 || max_page > page) {
                    var query = $('#search_text').val();
                    var requestUrl = 'discussions';
                    if (query != null && query != '') {
                        requestUrl = 'searchDiscussions';
                        data = {page: ++page};
                    }
//                    else if (filter_tags.length > 0 || filter_basins.length > 0) {
//                        var filter_tag_ids = $("#filter_tags_id").val();
//                        var filter_basin_ids = $("#filter_basins_id").val();
//                        requestUrl = 'filters';
//                        data = {tag_ids: filter_tag_ids, basin_ids: filter_basin_ids, page: ++page};
//                    } 
                    else {
                        requestUrl = 'discussions';
                        data = {page: ++page};
                    }
                    $.ajax({
                        url: APP_URL + requestUrl,
                        type: 'get',
                        dataType: 'json',
                        data: data,
                        success: function (response) {

                            if (response.http_status == 200) {
                                $("#dashboard_bubbles").append(response.html);

                                page = response.currentPage;
                                max_page = response.lastPage;
                                console.log('currentPage');
                                console.log(page);
                                console.log(max_page);

                            } else if (response.http_status == 201) {
                                $("#dashboard_bubbles").html(response.html);
                            }
                            if (response.http_status == 440) {
                                window.location = APP_URL + "/";
                            }


                        },
                        error: function (data) {
                            var json = jQuery.parseJSON(data['responseText'])
                            if (json['http_status'] == 440) {
                                window.location = APP_URL + "/";
                            }
                            $('#discussionErrorMessage').addClass('error').show().text(json['message']);
                            setTimeout(function () {
                                $('#discussionErrorMessage').hide().removeClass('error');
                            }, 10000);
                        }

                    });
                }

            }

        });
    }



});


function editDiscussion(discussion_id) {
    $("#popup_mask").removeClass('hide');
//        $(".tags-list").removeClass("tag-active");
    $("#launch_discussion_data").removeClass('hide');
//        $("#launch_discussion_form").trigger("reset");
//        showUploadPicture();


}
function discussionFilterLoadmore() {

    console.log('loadPage');
    console.log(page);

    console.log(max_page);
    var tag_ids = $("#filter_tags_id").val();
    var basin_ids = $("#filter_basins_id").val();
    if (tag_ids.length <= 0 && basin_ids.length <= 0) {
        $("#fiters_error").html('Select basin or tags.').addClass('colorRed');
        return false;
    }
    if (page == 1 || max_page > page) {
        $("#fiters_error").html('');
        $.ajax({
            url: APP_URL + "filters",
            data: {tag_ids: tag_ids, basin_ids: basin_ids, page: ++page},
            dataType: 'json',
//            async: true,
            type: 'get',
//            processData: false,
//            contentType: false,
            success: function (response) {
                console.log(response.http_status);
                if (response.http_status == 200) {
                    page = response.currentPage;
                    max_page = response.lastPage;
                    console.log('loadMorepage');
                    console.log(page);
                    console.log(max_page);
                    $("#dashboard_bubbles").append(response.html);
                }
                if (response.http_status == 203) {
                    $("#fiters_error").addClass('colorRed').text(response.message);
                }
                if (response.http_status == 440) {
                    window.location = APP_URL + "/";
                }


            },
            error: function (data) {
                var json = jQuery.parseJSON(data['responseText'])
                if (json['http_status'] == 440) {
                    window.location = APP_URL + "/";
                }

                $("#fiters_error").addClass('colorRed').text(json['message']);
                setTimeout(function () {
                    $("#fiters_error").removeClass('colorRed');
                    $("#fiters_error").hide();
                }, 10000);
            }

        });
    }

}

function discussionFilter() {

    var tag_ids = $("#filter_tags_id").val();
    var basin_ids = $("#filter_basins_id").val();
    if (tag_ids.length <= 0 && basin_ids.length <= 0) {
        $("#fiters_error").html('Select basin or tags.').addClass('colorRed');
        return false;
    }
    $("#fiters_error").html('');
    $.ajax({
        url: APP_URL + "filters",
        data: {tag_ids: tag_ids, basin_ids: basin_ids, page: 1},
        dataType: 'json',
//            async: true,
        type: 'get',
//            processData: false,
//            contentType: false,
        success: function (response) {
            console.log(response.http_status);
            if (response.http_status == 200) {
                page = response.currentPage;
                max_page = response.lastPage;
                console.log('page');
                console.log(page);
                console.log(max_page);
                $("#dashboard_bubbles").html(response.html);
            }
            if (response.http_status == 203) {
                $("#fiters_error").addClass('colorRed').text(response.message);
            }
            if (response.http_status == 440) {
                window.location = APP_URL + "/";
            }


        },
        error: function (data) {
            var json = jQuery.parseJSON(data['responseText'])
            if (json['http_status'] == 440) {
                window.location = APP_URL + "/";
            }

            $("#fiters_error").addClass('colorRed').text(json['message']);
            setTimeout(function () {
                $("#fiters_error").removeClass('colorRed');
                $("#fiters_error").hide();
            }, 10000);
        }

    });
}

//function clearFilter() {
//    var tag_ids = false;
//    $("#filter_tags_id").val('')
//    var basin_ids = false;
//    $("#filter_basins_id").val('');
//    $.ajax({
//        url: APP_URL + "searchDiscussions",
////        data: {tag_ids: tag_ids, basin_ids: basin_ids},
//        dataType: 'json',
////            async: true,
//        type: 'get',
////            processData: false,
////            contentType: false,
//        success: function (response) {
////            console.log(response.http_status);
//            if (response.http_status == 200) {
//                $('.basins_name').removeClass("active");
//                $(".pick-topic").removeAttr("style");
//                $("#dashboard_bubbles").html(response.html);
//            }
//            if (response.http_status == 203) {
//                $("#fiters_error").addClass('colorRed').text(response.message);
//            }
//            if (response.http_status == 440) {
//                window.location = APP_URL + "/";
//            }
//
//
//        },
//        error: function (data) {
//            var json = jQuery.parseJSON(data['responseText'])
//            if (json['http_status'] == 440) {
//                window.location = APP_URL + "/";
//            }
//
//            $("#fiters_error").addClass('colorRed').text(json['message']);
//            setTimeout(function () {
//                $("#fiters_error").removeClass('colorRed');
//                $("#fiters_error").hide();
//            }, 10000);
//        }
//
//    });
//}

function  openDiscussion(discussion_id) {
    window.location = APP_URL + "discussion/" + discussion_id;
    return false;
}

function openSearchDiscussion(discussion_id, search_type, result_id) {
    window.location = APP_URL + "search/" + discussion_id + "/" + search_type + '/' + result_id;
    return false;
}

function  openExploreDiscussion(discussion_id) {


    window.location = APP_URL + "exploreDetails/" + discussion_id;
    return false;
}

function ajaxOpenDiscussion(discussion_id) {
    $.ajax({
        url: APP_URL + "openDiscussion",
        type: 'post',
        dataType: 'json',
        data: {discussion_id: discussion_id},
//        async: true,
//        processData: false,
//        contentType: false,
        success: function (response) {
            $("#dashboard_bubbles").hide();
            $('#dashboard_discussion_tab').empty().text();
            if (response.http_status == 200) {
                $("#launch_discussion").hide();
                $("#about_discussion_basin_name").show();
                $("#discussion_basin_name").text(response.basin_name);
                $("#discussion_basin_name").css('color', response.basin_color_code);
                $("#dashboard_discussion_tab").show();
                $("#dashboard_discussion_tab").empty().html(response.html);
            } else if (response.http_status == 201) {
                $("#dashboard_discussion_tab").empty().html(response.html);
            }
            if (response.http_status == 440) {
                window.location = APP_URL + "/";
            }


        },
        error: function (data) {
            var json = jQuery.parseJSON(data['responseText'])
            if (json['http_status'] == 440) {
                window.location = APP_URL + "/";
            }
            $('#discussionErrorMessage').addClass('error').show().text(json['message']);
            setTimeout(function () {
                $('#discussionErrorMessage').hide().removeClass('error');
            }, 10000);
        }

    });
}

function loadMoreReplies(discussion_id, last_reply_id, type) {
    $.ajax({
        url: APP_URL + "loadMoreReplies",
        type: 'post',
        dataType: 'json',
        data: {discussion_id: discussion_id, last_reply_id: last_reply_id, type: type},
//        async: true,
//        processData: false,
//        contentType: false,
        success: function (response) {
            $("#dashboard_bubbles").hide();
            $('#dashboard_discussion_tab').empty().text();
            if (response.http_status == 200) {
                $("#dashboard_discussion_tab").show();
                $("#dashboard_discussion_tab").empty().html(response.html);
            } else if (response.http_status == 201) {
                $("#dashboard_discussion_tab").empty().html(response.html);
            }
            if (response.http_status == 440) {
                window.location = APP_URL + "/";
            }


        },
        error: function (data) {
            var json = jQuery.parseJSON(data['responseText'])
            if (json['http_status'] == 440) {
                window.location = APP_URL + "/";
            }
            $('#discussionErrorMessage').addClass('error').show().text(json['message']);
            setTimeout(function () {
                $('#discussionErrorMessage').hide().removeClass('error');
            }, 10000);
        }

    });
}

function openReply() {
    $("#add_reply_content").remove();
    $("#add_reply_button").addClass("disabledbutton");
//    $(".box4").hide();
    $("#dashboard_discussion_tab").append('<div class="box4" id="add_reply_content"> <textarea class="reply-textarea" maxlength="1000" placeholder="Type your reply here. &#13;&#10;Limit of 1000 Characters" id="reply_input" onfocus="this.placeholder =``"  onblur="this.placeholder = `Type your reply here. &#13;&#10; Limit of 1000 Characters.`"></textarea> <p class="ta-limit" id="reply_error"></p><div class="img-upload"><input type="file" accept="image/*" id="reply_image_upload" onchange="loadFile(event)" /><img class="custom-image" src="' + APP_URL + '/resources/assets/images/camera_icon.png" /><span class="upload-text" onClick="showImageUpload()">Upload Photo</span></div><p class="post-done" id="post_done" onclick="postReply();">Done</p></div>');
//     activateMentions(0, null);
    $('.reply-textarea').mentionsInput({
//            defaultValue  : '@[Peter Jones](id:1)', 
        onDataRequest: function (mode, query, callback) {
            $.getJSON(APP_URL + "usersList", function (responseData) {
                responseData = _.filter(responseData, function (item) {
                    return item.name.toLowerCase().indexOf(query.toLowerCase()) > -1;
                });
                callback.call(this, responseData);
            });
        }
    });
    $("#reply_input").focus();
    $("#reply_input").blur();
//    $("#reply_input").focus();

//    activateMentions(0, null);
}

function postReply() {
    var main_reply = $('#reply_input').val();
    var reply = '';
    $('#reply_input').mentionsInput('val', function (text) {
        reply = text;
    });
    var discussion_id = $("#discussion_id").val();
    $('#post_done').css('pointer-events', 'none');
//     $('#post_done').hide();
    if (main_reply.length <= 0) {
        $("#reply_error").html('Please enter reply.');
        $("#reply_error").addClass('colorRed');
        $('#post_done').css('pointer-events', 'auto');
        return false;
    }
    var box_alignment = $("#box_alignment").val();
    var form_data = new FormData();

    form_data.append("upload_file", document.getElementById('reply_image_upload').files[0]);
    form_data.append("discussion_id", discussion_id);
    form_data.append("reply", reply);
    form_data.append("box_alignment", box_alignment);
    form_data.append("user_reply", main_reply);
    $.ajax({
        url: APP_URL + "postReply",
        type: 'post',
        data: form_data,
        dataType: 'json',
        async: true,
        type: 'post',
        processData: false,
        contentType: false,
        success: function (response) {
            if (response.http_status == 200) {
                $("#dashboard_discussion_tab").append(response.html);
                $("#reply_error").removeClass('colorRed').addClass('colorGreen');
                $("#reply_error").html(response.message);
                $("#add_reply_content").remove();
                $("#add_reply_button").removeClass("disabledbutton");
                if (box_alignment == 1) {
                    $("#box_alignment").val(2);
                } else {
                    $("#box_alignment").val(1);
                }
            }
            if (response.http_status == 440) {
                window.location = APP_URL + "/";
            }


        },
        error: function (data) {
            var json = jQuery.parseJSON(data['responseText'])
            if (json['http_status'] == 440) {
                window.location = APP_URL + "/";
            }
            $('#reply_error').addClass('colorRed').show().text(json['message']);
            setTimeout(function () {
                $('#reply_error').hide().removeClass('colorRed');
                $('#post_done').css('pointer-events', 'auto');
            }, 10000);
            $("#add_reply_button").removeClass("disabledbutton");
        }

    });
}

function openComment(reply_id) {
    $("#add_comment_content").remove();
    $("#add_comment_button").addClass("disabledbutton");
    $("#reply_box_" + reply_id).append('<div  id="add_comment_content"> <textarea class="post-textarea" maxlength="1000" placeholder="Type your comment here.&#13;&#10;Limit of 1000 Characters" onfocus="this.placeholder =``"  onblur="this.placeholder = `Type your comment here. &#13;&#10; Limit of 1000 Characters.`" id="comment_input_' + reply_id + '"></textarea> <p class="ta-limit" id="comment_error_' + reply_id + '"> </p><div class="img-upload"><input type="file" accept="image/*" id="comment_image_upload" onchange="loadFile(event)" /><img class="custom-image" src="' + APP_URL + '/resources/assets/images/camera_icon.png" /><span class="upload-text" onClick="showImageUpload()">Upload Photo</span></div><p class="post-submit" id="post_submit" onclick="postComment(' + reply_id + ');">Done</p></div>');
    $('.post-textarea').mentionsInput({
//            defaultValue  : '@[Peter Jones](contact:200)', 
        onDataRequest: function (mode, query, callback) {
            $.getJSON(APP_URL + "usersList", function (responseData) {
                responseData = _.filter(responseData, function (item) {
                    return item.name.toLowerCase().indexOf(query.toLowerCase()) > -1;
                });
                callback.call(this, responseData);
            });
        }
    });
    $("#comment_input_" + reply_id).focus();
    $("#comment_input_" + reply_id).trigger('blur');
    $("#comment_input_" + reply_id).focus();
    $("#add_reply_content").remove();
}

function postComment(reply_id) {
    var comment = $('#comment_input_' + reply_id).val();

    if (comment.length <= 0) {
        $("#comment_error_" + reply_id).html('Please enter comment.');
        $("#comment_error_" + reply_id).addClass('colorRed');
        return false;
    }
    $('#comment_input_' + reply_id).mentionsInput('val', function (text) {
        comment = text;
    });
    var discussion_id = $("#discussion_id").val();

    var form_data = new FormData();

    form_data.append("upload_file", document.getElementById('comment_image_upload').files[0]);
    form_data.append("discussion_id", discussion_id);
    form_data.append("comment", comment);
    form_data.append("comment", comment);
    form_data.append("reply_id", reply_id);
    $.ajax({
        url: APP_URL + "postComment",
        type: 'post',
        data: form_data,
        dataType: 'json',
        async: true,
        processData: false,
        contentType: false,
        success: function (response) {
//            alert(response.http_status);
            if (response.http_status == 200 || response.http_status == 0) {
                var chat_bubble__count = $("#chat_bubble__count_" + reply_id).text();
                chat_bubble__count++;
                
                $("#reply_box_" + reply_id).append(response.html);
                $("#comment_error_" + reply_id).removeClass('colorRed').addClass('colorGreen');
                $("#comment_error_" + reply_id).html(response.message);
//                setTimeout(function () {
//                    $("#add_comment_content").remove();
//                }, 2000);

                $("#add_comment_content").remove();
                $("#chat_bubble__count_" + reply_id).text(chat_bubble__count);
            }
            if (response.http_status == 440) {
                window.location = APP_URL + "/";
            }


        },
        error: function (data) {
            var json = jQuery.parseJSON(data['responseText'])
            if (json['http_status'] == 440) {
                window.location = APP_URL + "/";
            }
            $('#comment_error_' + reply_id).addClass('colorRed').show().text(json['message']);
            setTimeout(function () {
                $('#comment_error_' + reply_id).hide().removeClass('colorRed');
            }, 10000);
        }

    });
}

function loadComments(reply_id, type) {
    var discussion_id = $("#discussion_id").val();
//    $("#chat_bubble_" + reply_id).addClass("disabledbutton");
    if ($('#comments_section_' + reply_id).find('.comment-box ').length) {
        $("#comments_section_" + reply_id).empty();
        $('#reply_box_' + reply_id + ' .comment-box').remove();
    } else {

        $.ajax({
            url: APP_URL + "loadComments",
            type: 'post',
            dataType: 'json',
            data: {discussion_id: discussion_id, type: type, reply_id: reply_id},
//        async: true,
//        processData: false,
//        contentType: false,
            success: function (response) {
                if (response.http_status == 200) {

                    $('#reply_box_' + reply_id).remove('.comment-box');
//                $("#reply_box_" + reply_id).append(response.html);
                    $("#comments_section_" + reply_id).empty().html(response.html);
                    $("#comment_error_" + reply_id).removeClass('colorRed').addClass('colorGreen');
                    $("#comment_error_" + reply_id).html(response.message);
//                setTimeout(function () {
//                    $("#add_comment_content").remove();
//                }, 2000);
                    $("#add_comment_content").remove();
                }
//            if (response.http_status == 201) {
//                
//            }
                if (response.http_status == 440) {
                    window.location = APP_URL + "/";
                }


            },
            error: function (data) {
                var json = jQuery.parseJSON(data['responseText'])
                if (json['http_status'] == 440) {
                    window.location = APP_URL + "/";
                }
                $('#comment_error_' + reply_id).addClass('colorRed').show().text(json['message']);
                setTimeout(function () {
                    $('#comment_error_' + reply_id).hide().removeClass('colorRed');
                }, 10000);
            }

        });
    }
}

function makeFavoriteReply(reply_id, type) {
    var discussion_id = $("#discussion_id").val();
    console.log(type);
    //console.log("#favorite_discussion_bubble_" + reply_id);
//    if (type == 1) {
//        $("#favorite_discussion_bubble_" + reply_id).addClass("disabledbutton");
//    }
//    if (type == 2) {
//        $("#favorite_bubble_" + reply_id).addClass("disabledbutton");
//    }
    $.ajax({
        url: APP_URL + "makeFavoriteReply",
        type: 'post',
        dataType: 'json',
        data: {discussion_id: discussion_id, type: type, reply_id: reply_id},
//        async: true,
//        processData: false,
//        contentType: false,
        success: function (response) {
            if (response.http_status == 200) {
               // console.log(response.test);

                if (type == 1) {
                    var f_count = $("#favorite_discussion_bubble_count_" + reply_id).text();
                    if ($("#favorite_discussion_bubble_" + reply_id).hasClass('post-like active'))
                    {
                        $("#favorite_discussion_bubble_" + reply_id).removeClass("post-like active");
                        $("#favorite_discussion_bubble_count_" + reply_id).html(--f_count);
                    } else {
                        $("#favorite_discussion_bubble_" + reply_id).addClass("post-like active");
                        $("#favorite_discussion_bubble_count_" + reply_id).html(++f_count);
                    }


                }
                if (type == 2) {
                    var f_count = $("#favorite_bubble_count_" + reply_id).text();
                    if ($("#favorite_bubble_" + reply_id).hasClass('post-like active'))
                    {
                        $("#favorite_bubble_" + reply_id).removeClass("post-like active");
                        $("#favorite_bubble_count_" + reply_id).html(--f_count);
                    } else {
                        $("#favorite_bubble_" + reply_id).addClass("post-like active");
                        $("#favorite_bubble_count_" + reply_id).html(++f_count);
                    }
                }
                if (type == 3) {
                    var f_count = $("#favorite_comment_bubble_count_" + reply_id).text();
                    if ($("#favorite_comment_bubble_" + reply_id).hasClass('post-like active'))
                    {
                        $("#favorite_comment_bubble_" + reply_id).removeClass("post-like active");
                        $("#favorite_comment_bubble_count_" + reply_id).html(--f_count);
                    } else {
                        $("#favorite_comment_bubble_" + reply_id).addClass("post-like active");
                        $("#favorite_comment_bubble_count_" + reply_id).html(++f_count);
                    }
                }

            }
            if (response.http_status == 440) {
                window.location = APP_URL + "/";
            }


        },
        error: function (data) {
            var json = jQuery.parseJSON(data['responseText'])
            if (json['http_status'] == 440) {
                window.location = APP_URL + "/";
            }
            if (type == 1) {
                $('#discussion_error_' + reply_id).hide().addClass('colorRed').show().text(json['message']);
            } else {
                $('#reply_error_' + reply_id).hide().addClass('colorRed').show().text(json['message']);
            }
            setTimeout(function () {
                if (type == 1) {
                    $('#discussion_error_' + reply_id).hide().removeClass('colorRed');
                } else {
                    $('#reply_error_' + reply_id).hide().removeClass('colorRed');
                }

            }, 10000);
        }

    });
}

function reportPost(id, type, reply_id) {
    $("#report_post_id").val(id);
    $("#report_post_type").val(type);
    $("#report_reply_id").val(reply_id);
    $("#popup_mask").removeClass('hide');
    $("#discussion_report_reasons").removeClass('hide');
    $("#discussion_report_reasons").show();
    $('.report_reason_checkbox').prop("checked", false);
}
function reportPostData() {
    var discussion_id = $("#discussion_id").val();
    var id = $("#report_post_id").val();
    var type = $("#report_post_type").val();
    var report_reason = $("input[name='report_reason[]']:checked").val();
    if ($("input[name='report_reason[]']:checked").length == 0 || $("input[name='report_reason[]']:checked").val() == undefined) {
        $("#report_reason_error").addClass('colorRed').text("Please select reason.");
        setTimeout(function () {
            $("#report_reason_error").removeClass('colorRed');
            $("#report_reason_error").text('');
        }, 2000);
        return false;
    }


    $.ajax({
        url: APP_URL + "reportPost",
        type: 'post',
        dataType: 'json',
        data: {discussion_id: discussion_id, type: type, reply_id: id, report_reason: report_reason},
        success: function (response) {
            if (response.http_status == 200) {
                if (type == 2) {
                    $("#reply_box_" + id).hide();
                }
                if (type == 3) {

                    $("#comment_box_" + id).hide();
                    var reply_id = $("#report_reply_id").val();
                    var comments_count = $("#chat_bubble__count_" + reply_id).text();
                    $("#chat_bubble__count_32").text(--comments_count);
                }
                $("#discussion_report_reasons").addClass('hide').hide();
                $("#discussion_resport_success").removeClass("hide");
                $("#discussion_resport_success").show();
            }
            if (response.http_status == 440) {
                window.location = APP_URL + "/";
            }


        },
        error: function (data) {
            var json = jQuery.parseJSON(data['responseText'])
            if (json['http_status'] == 440) {
                window.location = APP_URL + "/";
            }

            $("#report_reason_error").addClass('colorRed').text(json['message']);
            setTimeout(function () {
                $("#report_reason_error").removeClass('colorRed');
                $("#report_reason_error").hide();
            }, 10000);
        }

    });
}

function followDiscussion(discussion_id) {
    var discussion_id = $("#discussion_id").val();
    $.ajax({
        url: APP_URL + "followDiscussion",
        type: 'post',
        dataType: 'json',
        data: {discussion_id: discussion_id},
        success: function (response) {
            if (response.http_status == 200) {
                $("#follow_discussion").text('Unfollow Discussion');
                $("#follow_discussion").removeAttr('onclick');
                $("#follow_discussion").attr("onclick", "unFollowDiscussion(" + discussion_id + ")");
                $("#post_dots").removeAttr('onclick');
                $("#post_dots").attr("onclick", "genReports(" + discussion_id + ",1,1,0)");
                $("#about_discussion").text("You're now following this discussion about ");
                $(".report").hide();
                $("#follow_icon").show();
            }
            if (response.http_status == 440) {
                window.location = APP_URL + "/";
            }
        },
        error: function (data) {
            var json = jQuery.parseJSON(data['responseText'])
            if (json['http_status'] == 440) {
                window.location = APP_URL + "/";
            }

            $("#report_reason_error").addClass('colorRed').text(json['message']);
            setTimeout(function () {
                $("#report_reason_error").removeClass('colorRed');
                $("#report_reason_error").hide();
            }, 10000);
        }

    });
}
function unFollowDiscussion(discussion_id) {
    var discussion_id = $("#discussion_id").val();
    $.ajax({
        url: APP_URL + "followDiscussion",
        type: 'post',
        dataType: 'json',
        data: {discussion_id: discussion_id},
        success: function (response) {
            if (response.http_status == 200) {
                $("#about_discussion").text("You're unfollwed this discussion about ");
                $("#follow_discussion").text('Follow Discussion');
                $("#post_dots").removeAttr('onclick');
                $("#post_dots").attr("onclick", "genReports(" + discussion_id + ",1,0,0)");
                $("#follow_discussion").removeAttr("onclick");
                $("#follow_discussion").attr("onclick", "followDiscussion(" + discussion_id + ")");
                $(".report").hide();
                $("#follow_icon").hide();
            }
            if (response.http_status == 440) {
                window.location = APP_URL + "/";
            }
        },
        error: function (data) {
            var json = jQuery.parseJSON(data['responseText'])
            if (json['http_status'] == 440) {
                window.location = APP_URL + "/";
            }

            $("#report_reason_error").addClass('colorRed').text(json['message']);
            setTimeout(function () {
                $("#report_reason_error").removeClass('colorRed');
                $("#report_reason_error").hide();
            }, 10000);
        }

    });
}

function addPoll(discussion_id) {
    $("#popup_mask").removeClass('hide');
    $("#add_poll").removeClass('hide');
    $("#add_poll").show();
    $("#poll_question").val('');
}

function postPoll() {
    $("#poll_error").html('');
    var discussion_id = $("#discussion_id").val();
    var question = $("#poll_question").val();
    var poll_type = $("input[name='poll_type[]']:checked").val();
    if (question.length < 5) {
        $("#poll_error").html("Please enter poll question.").addClass('colorRed');
        return false;
    }
    if ($("input[name='poll_type[]']:checked").length == 0 || $("input[name='poll_type[]']:checked").val() == undefined) {
        $("#poll_error").html("Please select poll type.").addClass('colorRed');
        return false;
    }


    $.ajax({
        url: APP_URL + "postNewPoll",
        type: 'post',
        dataType: 'json',
        data: {discussion_id: discussion_id, question: question, poll_type: poll_type},
        success: function (response) {
            if (response.http_status == 200) {

                $("#add_poll").addClass('hide').hide();
                $("#popup_mask").addClass('hide');
                $("#dashboard_discussion_tab").append(response.html);
                $('input[name="poll_type[]"]').each(function () {
                    this.checked = false;
                });
            }

            if (response.http_status == 440) {
                window.location = APP_URL + "/";
            }


        },
        error: function (data) {
            var json = jQuery.parseJSON(data['responseText'])
            if (json['http_status'] == 440) {
                window.location = APP_URL + "/";
            }

            $("#poll_error").addClass('colorRed').text(json['message']);
            setTimeout(function () {
                $("#poll_error").removeClass('colorRed');
                $("#poll_error").hide();
            }, 10000);
        }

    });
}


function postAnswer(poll_id, poll_answer_id) {
    var discussion_id = $("#discussion_id").val();
    $.ajax({
        url: APP_URL + "answerPollQuestion",
        type: 'post',
        dataType: 'json',
        data: {discussion_id: discussion_id, poll_id: poll_id, answer_id: poll_answer_id},
        success: function (response) {
            if (response.http_status == 200) {
                $('.poll-sel').each(function (index, value) {
                    $(this).addClass('disabledbutton');
                });
                $("#poll_response_" + poll_id).addClass('colorGreen').text(response.message);
            }
            if (response.http_status == 203) {
                $("#poll_response_" + poll_id).addClass('colorGreen').text(response.message);
            }
            if (response.http_status == 440) {
                window.location = APP_URL + "/";
            }


        },
        error: function (data) {
            var json = jQuery.parseJSON(data['responseText'])
            if (json['http_status'] == 440) {
                window.location = APP_URL + "/";
            }

            $("#poll_response_" + poll_id).addClass('colorRed').text(json['message']);
            setTimeout(function () {
                $("#poll_response_" + poll_id).removeClass('colorRed');
                $("#poll_response_" + poll_id).hide();
            }, 10000);
        }

    });
}


function postAnswer2() {
    console.log('hi');
    $("#popup_mask2").removeClass('hide');
    $("#poll_own_answer").removeClass('hide');
}

function scheduleMeeting(discussion_id) {
    $("#post_meeting_error").html('');
    $("#popup_mask").removeClass('hide');
    $("#schedule_meeting").removeClass('hide');
    $("#schedule_meeting").show();
    $("#street_1").val('');
    $("#city").val('');
    $("#state").val('');
    $("#zipcode").val('');
    $("#venue_name").val('');
    $("#meeting_date").val('');
    $("#start_time").val('');
    $("#end_time").val('');
    $("#purpose_of_meeting").val('');
}

function postMeeting() {
    $("#post_meeting_error").html('');
    var discussion_id = $("#discussion_id").val();
    var street_1 = $("#street_1").val();
    var city = $("#city").val();
    var state = $("#state").val();
    var zipcode = $("#zipcode").val();
    var venue_name = $("#venue_name").val();
    var meeting_date = $("#meeting_date").val();
    var start_time = $("#start_time").val();
    var end_time = $("#end_time").val();
    var purpose_of_meeting = $("#purpose_of_meeting").val();
    var meeting_id = $("#meeting_id").val();
    var additional_conference_details = $("#additional_conference_details").val();
    if ((street_1.length < 1 && city.length < 2 && state.length < 2 && zipcode.length < 2) && venue_name.length < 5) {
        $("#post_meeting_error").html("Please enter address or Video Conference Link.").addClass('colorRed');
        return false;
    }

    if (meeting_date.length < 1) {
        $("#post_meeting_error").html("Please selecte meeting date.").addClass('colorRed');
        return false;
    }
    if (start_time.length < 1) {
        $("#post_meeting_error").html("Please select meeting start time.").addClass('colorRed');
        return false;
    }
    var currentTime = new Date();
    var hours = currentTime.getHours();
    var minutes = currentTime.getMinutes();
    var differenceDate = parseInt(currentTime) - parseInt(meeting_date);
    var timeParts = start_time.split(":");
    var timeParts2 = end_time.split(":");
//    console.log(timeParts2);
//    console.log(hours);
//    console.log(minutes);
//    console.log(differenceDate);
//    console.log(timeParts);
//    console.log(parseInt(timeParts[0]));
    if ((differenceDate >= 0) && (parseInt(hours) >= parseInt(timeParts[0])) && (parseInt(minutes) > parseInt(timeParts[1]))) {
        $("#post_meeting_error").html("Start time should be future time..").addClass('colorRed');
        return false;
    }
    if (end_time.length < 1) {
        $("#post_meeting_error").html("Please select meeting end time.").addClass('colorRed');
        return false;
    }
    if ((parseInt(timeParts[0]) >= parseInt(timeParts2[1])) && (parseInt(timeParts[1]) > parseInt(timeParts2[1]))) {
        $("#post_meeting_error").html("End time should be greater than start time.").addClass('colorRed');
        return false;
    }
    if (purpose_of_meeting.length < 1) {
        $("#post_meeting_error").html("Please enter purpose of meeting.").addClass('colorRed');
        return false;
    }



    $.ajax({
        url: APP_URL + "postMeeting",
        type: 'post',
        dataType: 'json',
        data: {discussion_id: discussion_id, meeting_id: meeting_id, street_1: street_1, city: city, state: state, zipcode: zipcode, venue_name: venue_name, meeting_date: meeting_date, start_time: start_time, end_time: end_time, purpose_of_meeting: purpose_of_meeting, additional_conference_details: additional_conference_details},
        success: function (response) {
            if (response.http_status == 200) {

                $("#schedule_meeting").addClass('hide').hide();
                $("#popup_mask").addClass('hide');
                $("#meeting_list_entry").after(response.html);
                $("#street_1").val('');
                $("#city").val('');
                $("#state").val('');
                $("#zipcode").val('');
                $("#venue_name").val('');
                $("#meeting_date").val('');
                $("#start_time").val('');
                $("#end_time").val('');
                $("#purpose_of_meeting").val('');
                $("#additional_conference_details").val('');
            }
            if (response.http_status == 203) {
                $("#post_meeting_error").addClass('colorRed').text(response.message);
            }
            if (response.http_status == 440) {
                window.location = APP_URL + "/";
            }


        },
        error: function (data) {
            var json = jQuery.parseJSON(data['responseText'])
            if (json['http_status'] == 440) {
                window.location = APP_URL + "/";
            }

            $("#post_meeting_error").addClass('colorRed').text(json['message']);
            setTimeout(function () {
                $("#post_meeting_error").removeClass('colorRed');
                $("#post_meeting_error").hide();
            }, 10000);
        }

    });
}


function editMeetingDetails(meeting_id) {
    $("#street_1").val('');
    $("#city").val('');
    $("#state").val('');
    $("#zipcode").val('');
    $("#venue_name").val('');
    $("#meeting_date").val('');
    $("#start_time").val('');
    $("#end_time").val('');
    $("#purpose_of_meeting").val('');
    $.ajax({
        url: APP_URL + "getMeetingDetails",
        type: 'get',
        dataType: 'json',
        data: {meeting_id: meeting_id},
        success: function (response) {
            if (response.http_status == 200) {

                $("#schedule_meeting").removeClass('hide').show();
                $("#popup_mask").removeClass('hide');
                $("#street_1").val(response.data.street_1);
                $("#city").val(response.data.city);
                $("#state").val(response.data.state);
                $("#zipcode").val(response.data.zipcode);
                $("#venue_name").val(response.data.venue_name);
                $("#meeting_date").val(response.data.meeting_date);
                var start_time = tConv24(response.data.start_time);
                var end_time = tConv24(response.data.end_time);
                $("#start_time").val(start_time);
                $("#end_time").val(end_time);
                $("#purpose_of_meeting").val(response.data.purpose_of_meeting);
                $("#additional_conference_details").val(response.data.additional_conference_details);
                $("#meeting_id").val(meeting_id);
            }
            if (response.http_status == 203) {
                $("#post_meeting_error").addClass('colorRed').text(response.message);
            }
            if (response.http_status == 440) {
                window.location = APP_URL + "/";
            }


        },
        error: function (data) {
            var json = jQuery.parseJSON(data['responseText'])
            if (json['http_status'] == 440) {
                window.location = APP_URL + "/";
            }

            $("#post_meeting_error").addClass('colorRed').text(json['message']);
            setTimeout(function () {
                $("#post_meeting_error").removeClass('colorRed');
                $("#post_meeting_error").hide();
            }, 10000);
        }

    });
}


function veiwMeetingDetails(meeting_id) {
    $.ajax({
        url: APP_URL + "getMeetingDetails",
        type: 'get',
        dataType: 'json',
        data: {meeting_id: meeting_id},
        success: function (response) {
            if (response.http_status == 200) {

                $("#schedule_attend").removeClass('hide');
                $("#popup_mask").removeClass('hide');
                $("#show_meeting_date").html(response.data.meeting_date);
                var start_time = tConv24(response.data.start_time);
                var end_time = tConv24(response.data.end_time);
                $("#show_meeting_time").html(start_time + '-' + end_time + '  EST');
                var venue_name = response.data.venue_name;
                if (venue_name != null && venue_name.length > 0) {
                    $("#meeting_place").html(response.data.venue_name);
                } else {
                    $("#meeting_place").html(response.data.street_1 + '<br>' + response.data.city + '<br>' + response.data.state + '<br>' + response.data.zipcode);
                }


                $("#meeting_purpose").text("The purpose of the meeting is " + response.data.purpose_of_meeting);
                $("#meeting_usser_profile_icon").attr("src", response.data.user_profile_icon);
                $("#meeting_user_name").text(response.data.user_name);
                if (response.data.additional_conference_details != null) {
                    $("#additiona_info_div").show();
                    $("#additiona_info").text(response.data.additional_conference_details);
                } else {
                    $("#additiona_info_div").hide();
                }
                $("#show_meeting_id").val(meeting_id);
                if (response.meeting_status == 1) {

                    $('#sch_meeeting_view').text('You’re Attending');
                    $("#sch_alert_not_attend").removeClass('hide');
                    $("#sch_alert_attend").addClass('hide');
                }
                if (response.meeting_status == 2) {
                    $('#sch_meeeting_view').text('You’re currently viewing');
                    $("#sch_alert_attend").removeClass('hide');
                    $("#sch_alert_not_attend").addClass('hide');
                }


            }
            if (response.http_status == 203) {
                $("#post_meeting_error").addClass('colorRed').text(response.message);
            }
            if (response.http_status == 440) {
                window.location = APP_URL + "/";
            }


        },
        error: function (data) {
            var json = jQuery.parseJSON(data['responseText'])
            if (json['http_status'] == 440) {
                window.location = APP_URL + "/";
            }

            $("#post_meeting_error").addClass('colorRed').text(json['message']);
            setTimeout(function () {
                $("#post_meeting_error").removeClass('colorRed');
                $("#post_meeting_error").hide();
            }, 10000);
        }

    });
}


function attendMeeting() {
    var show_meeting_id = $("#show_meeting_id").val();
    $("#schedule_attend").addClass('hide');
    $("#schedule_confirm").removeClass('hide');
    $("#meeting_attend_id").val(show_meeting_id);
}

function notAttendMeeting() {
    $("#schedule_attend").addClass('hide');
    $("#schedule_checkbox").removeClass('hide');
}

function notGoingToMeeting() {
    var show_meeting_id = $("#show_meeting_id").val();
    $.ajax({
        url: APP_URL + "notGoingToMeeting",
        type: 'post',
        dataType: 'json',
        data: {meeting_id: show_meeting_id},
        success: function (response) {
            if (response.http_status == 200) {
                $("#schedule_attend").addClass('hide');
                $("#schedule_endbox_2").removeClass('hide');
                $("#sch_alert_attend").removeClass('hide');
            }
            if (response.http_status == 203) {
                $("#post_meeting_error").addClass('colorRed').text(response.message);
            }
            if (response.http_status == 440) {
                window.location = APP_URL + "/";
            }


        },
        error: function (data) {
            var json = jQuery.parseJSON(data['responseText'])
            if (json['http_status'] == 440) {
                window.location = APP_URL + "/";
            }

            $("#post_meeting_error").addClass('colorRed').text(json['message']);
            setTimeout(function () {
                $("#post_meeting_error").removeClass('colorRed');
                $("#post_meeting_error").hide();
            }, 10000);
        }

    });
}

function showMeetingDetails(type) {

    $("#schedule_done").removeClass('hide').addClass('hide');
    $("#schedule_endbox_2").removeClass('hide').addClass('hide');
    $("#schedule_endbox").addClass('hide');
    if (type == 1) {
        $('#sch_meeeting_view').text('You’re Attending');
        $("#schedule_attend").removeClass('hide');
    }
    if (type == 2) {

        $('#sch_meeeting_view').text('You’re currently viewing');
        $("#sch_alert_not_attend").addClass('hide');
    }
    $("#schedule_attend").removeClass('hide');
}


function editReply(reply_id) {
    $(".box4").hide();
    $("#editReply_" + reply_id).addClass("disabledbutton");
    var post_reply = $("#post_reply_" + reply_id).text();
    $("#reply_box_" + reply_id).hide();
    $("#reply_box_" + reply_id).after('<div class="box4" id="add_reply_content"><textarea class="reply-textarea" maxlength="1000" placeholder="Type your reply here.&#13;&#10;Limit of 1000 Characters"  id="reply_input' + reply_id + '"></textarea><p class="ta-limit" id="reply_error' + reply_id + '"></p><div class="img-upload"><input type="file" accept="image/*" onchange="loadFile(event)"  id="reply_image_upload"/><img class="custom-image" src="' + APP_URL + '/resources/assets/images/camera_icon.png" /><span class="upload-text">Upload Photo</span></div><p class="post-done" id="post_done" onclick="updateReply(' + reply_id + ');">Done</p></div>');
    //<div class="img-upload"><input type="file" accept="image/*" id="reply_image_upload" onchange="loadFile(event)" /><img class="custom-image" src="'+APP_URL+'/resources/assets/images/camera_icon.png" /><span class="upload-text" onClick="showImageUpload()">Upload Photo</span></div>
    $.ajax({
        url: APP_URL + "getReplyText/" + reply_id,
        type: 'get',
        dataType: 'json',
        success: function (response) {

            if (response.http_status == 200) {
                $('.reply-textarea').mentionsInput({
                    defaultValue: response.post_reply, // '@[Dev](id:22)',
                    onDataRequest: function (mode, query, callback) {
                        $.getJSON(APP_URL + "usersList", function (responseData) {
                            responseData = _.filter(responseData, function (item) {
                                return item.name.toLowerCase().indexOf(query.toLowerCase()) > -1;
                            });
                            callback.call(this, responseData);
                        });
                    }
                });
                $("#reply_input" + reply_id).focus();
                $("#reply_input" + reply_id).keypress();
                $("#reply_input" + reply_id).keyup();
                // if image existsthen update text and image
                if (response.image_key_id != null) {
                    $(".custom-image").attr('src', response.image_key_id);
                    $('.upload-text').text('click here to update image');
                    $(".custom-image").attr('style', 'border-radius: 50%; width: 40px; height:40px;')
                }
            }
            if (response.http_status == 440) {
                window.location = APP_URL + "/";
            }
        },
        error: function (data) {
            var json = jQuery.parseJSON(data['responseText'])
            if (json['http_status'] == 440) {
                window.location = APP_URL + "/";
            }
            activateMentions(1, post_reply);
            $('.reply-textarea').focus();
        }

    });

}


function updateReply(reply_id) {

    var main_reply = $('#reply_input' + reply_id).val();
    if (main_reply.length <= 0) {
        $("#reply_error").html('Please enter reply.');
        $("#reply_error").addClass('red');
        return false;
    }
    var reply = null;
    $('#reply_input' + reply_id).mentionsInput('val', function (text) {
        reply = text;
    });
    var discussion_id = $("#discussion_id").val();
    $("#post_done").css('pointer-events', 'none');
    var form_data = new FormData();

    form_data.append("upload_file", document.getElementById('reply_image_upload').files[0]);
    form_data.append("discussion_id", discussion_id);
    form_data.append("reply_id", reply_id);
    form_data.append("reply", reply);
    form_data.append("box_alignment", box_alignment);
    form_data.append("user_reply", main_reply);

    var box_alignment = $("#box_alignment").val();
    $.ajax({
        url: APP_URL + "updateReply",
        type: 'post',
        data: form_data,
        dataType: 'json',
        async: true,
        type: 'post',
        processData: false,
        contentType: false,
        success: function (response) {

            if (response.http_status == 200) {
                $("#add_reply_content").remove();
                $("#reply_box_" + reply_id).show();
                $("#post_reply_" + reply_id).html(response.reply_text);
                $('#reply_image_' + reply_id).attr('src', response.image_key_id);
                $('#reply_image_' + reply_id).attr("onclick", "discussionImageView('" + response.image_key_id + "')");
                $("#editReply_" + reply_id).removeClass('disabledbutton');
            }
            if (response.http_status == 440) {
                window.location = APP_URL + "/";
            }
        },
        error: function (data) {
            var json = jQuery.parseJSON(data['responseText'])
            if (json['http_status'] == 440) {
                window.location = APP_URL + "/";
            }
            $('#reply_error').addClass('colorRed').show().text(json['message']);
            setTimeout(function () {
                $('#reply_error').hide().removeClass('colorRed');
                $("#post_done").css('pointer-events', 'auto');
            }, 10000);
        }

    });
}

function editComment(reply_id, parent_reply_id) {

    $("#editComment_" + reply_id).addClass("disabledbutton");
    $("#more_options_comment_" + reply_id).hide();
    var post_comment = $("#post_comment_" + reply_id).text();
    $("#post_comment_" + reply_id).hide();
    $("#editComment_" + reply_id).hide();
    $("#add_comment_button").addClass('disabledbutton');
    $("#comment_box_" + reply_id).append('<div  id="edit_comment_content"> <textarea class="post-textarea" maxlength="1000" placeholder="Type your comment here. &#13;&#10;Limit of 1000 Characters" onfocus="this.placeholder =``"  onblur="this.placeholder = "Type your comment here. &#13;&#10; Limit of 1000 Characters." id="comment_input_' + reply_id + '"></textarea> <p class="ta-limit" id="comment_error_' + reply_id + '"> </p><div class="img-upload"><input type="file" accept="image/*" id="comment_image_upload" onchange="loadFile(event)" /><img class="custom-image" src="' + APP_URL + '/resources/assets/images/camera_icon.png" /><span class="upload-text" onClick="showImageUpload()">Upload Photo</span></div><p class="post-submit" id="post_submit" onclick="updateComment(' + reply_id + ',' + parent_reply_id + ');">Done</p></div>');
    $.ajax({
        url: APP_URL + "getCommentText/" + reply_id,
        type: 'get',
        dataType: 'json',
        success: function (response) {

            if (response.http_status == 200) {
                $('.post-textarea').mentionsInput({
                    defaultValue: response.post_comment, // '@[Dev](contact:22)',
                    onDataRequest: function (mode, query, callback) {
                        $.getJSON(APP_URL + "usersList", function (responseData) {
                            responseData = _.filter(responseData, function (item) {
                                return item.name.toLowerCase().indexOf(query.toLowerCase()) > -1;
                            });
                            callback.call(this, responseData);
                        });
                    }
                });

                if (response.image_key_id != null) {
                    $(".custom-image").attr('src', response.image_key_id);
                    $('.upload-text').text('click here to update image');
                    $(".custom-image").attr('style', 'border-radius: 50%; width: 40px; height:40px;');
                    $("#comment_image_small_" + reply_id).hide();
                }
                $("#add_reply_content").remove();

            }
            if (response.http_status == 440) {
                window.location = APP_URL + "/";
            }


        },
        error: function (data) {
            var json = jQuery.parseJSON(data['responseText'])
            if (json['http_status'] == 440) {
                window.location = APP_URL + "/";
            }
//            activateCommentMentions(1, post_comment);
        }

    });

    $("#comment_input_" + reply_id).keypress();
    $("#comment_input_" + reply_id).keyup();
}

function updateComment(comment_id, reply_id) {
    var comment = $.trim($('#comment_input_' + comment_id).val());
    $('#comment_input_' + comment_id).mentionsInput('val', function (text) {
        comment = text;
    });
    var discussion_id = $("#discussion_id").val();
    $("#post_submit").css('pointer-events', 'none');
    if (comment.length <= 0) {
        $("#comment_error_" + comment_id).html('Please enter comment.');
        $("#comment_error_" + comment_id).addClass('colorRed');
        return false;
    }
    var form_data = new FormData();

    form_data.append("upload_file", document.getElementById('comment_image_upload').files[0]);
    form_data.append("discussion_id", discussion_id);
    form_data.append("comment", comment);
    form_data.append("comment_id", comment_id);
    form_data.append("reply_id", reply_id);
    $.ajax({
        url: APP_URL + "updateComment",
        type: 'post',
        dataType: 'json',
        data: form_data,
        async: true,
        processData: false,
        contentType: false,
        success: function (response) {
            if (response.http_status == 200) {
                $("#post_comment_" + comment_id).show().html(response.comment_text);
                $("#editComment_" + comment_id).show();
                $('#comment_image_' + comment_id).attr('src', response.image_key_id);
                $('#comment_image_' + comment_id).attr("onclick", "discussionImageView('" + response.image_key_id + "')");
                $("#comment_image_small_" + reply_id).show();
                $("#edit_comment_content").remove();
                $("#add_comment_button").removeClass("disabledbutton");
                $("#more_options_comment_" + comment_id).show();
                $("#comment_image_small_" + comment_id).show();
            }
            if (response.http_status == 440) {
                window.location = APP_URL + "/";
            }


        },
        error: function (data) {
            var json = jQuery.parseJSON(data['responseText'])
            if (json['http_status'] == 440) {
                window.location = APP_URL + "/";
            }

        }

    });
}


function deletePost(id, type, reply_id) {
    $("#delete_post_id").val(id);
    $("#delete_post_type").val(type);
    $("#delete_reply_id").val(reply_id);
    $("#popup_mask").removeClass('hide');
    $("#discussion_delete").removeClass('hide');
    $("#discussion_delete").show();
    $("#delete_no").show();
    $("#delete_yes").show();
    $("body").scrollTop(300);
    $("#discussion_delete_success").addClass('hide');
    $("#delete_confirm_prompt").show();
}
function deletePostData() {
    var discussion_id = $("#discussion_id").val();
    var id = $("#delete_post_id").val();
    var type = $("#delete_post_type").val();
    $("#delete_no").hide();
    $("#delete_yes").hide();
    $("#deleteLoader").show();
    $("#delete_confirm_prompt").hide();
    $.ajax({
        url: APP_URL + "deletePost",
        type: 'post',
        dataType: 'json',
        data: {discussion_id: discussion_id, type: type, reply_id: id},
        success: function (response) {
            if (response.http_status == 200) {
                if (type == 1) {
                    window.location = APP_URL + "discussions";
                }
                if (type == 2) {
                    $("#reply_box_" + id).hide();
                }
                if (type == 3) {
                    $("#comment_box_" + id).hide();
                    var reply_id = $("#delete_reply_id").val();
                    var comments_count = $("#chat_bubble__count_" + reply_id).text();
                    $("#chat_bubble__count_" + reply_id).text(--comments_count);
                }
//                $("#delete_confirm_prompt").addClass('hide');

                $("#discussion_delete_success").removeClass("hide");
//                $("#discussion_delete_success").show();
                $("#deleteLoader").hide();
            }
            if (response.http_status == 440) {
                window.location = APP_URL + "/";
            }


        },
        error: function (data) {
            var json = jQuery.parseJSON(data['responseText'])
            if (json['http_status'] == 440) {
                window.location = APP_URL + "/";
            }
            $("#delete_no").show();
            $("#delete_yes").show();
            $("#discussion_delete_success").addClass('colorRed').text(json['message']);
            setTimeout(function () {
                $("#discussion_delete_success").removeClass('colorRed');
                $("#discussion_delete_success").hide();
            }, 10000);
        }

    });
}


function learnMore() {
    $("#launch_conf").addClass('hide')
    $("#popup_mask").removeClass('hide');
    $("#launch_conf_learn_more").removeClass('hide');
}

function removeReportBg() {
    var type = $("#report_post_type").val();
    if (type == 1) {
        window.location = APP_URL + "/discussions";
    } else {
        $("#popup_mask").addClass('hide');
        $("#discussion_resport_success").addClass('hide');
    }
}


function tConv24(time24) {
    var ts = time24;
    var H = +ts.substr(0, 2);
    var h = (H % 12) || 12;
    h = (h < 10) ? ("0" + h) : h; // leading 0 at the left for 1 digit hours
    var ampm = H < 12 ? " AM" : " PM";
    ts = h + ts.substr(2, 3) + ampm;
    return ts;
}

function showHelpText() {
    $("#popup_mask").removeClass('hide');
    $("#unity_tool_help_text").removeClass('hide');
}

function hideHelpText() {
    $("#popup_mask").addClass('hide');
    $("#unity_tool_help_text").addClass('hide');
}


function activateMentions(type, post_reply = null) {

    if (type == 1) {
//        console.log(post_reply);
        $('.reply-textarea').mentionsInput({
            defaultValue: post_reply, // '@[Dev](id:22)',
            onDataRequest: function (mode, query, callback) {
                $.getJSON(APP_URL + "usersList", function (responseData) {
                    responseData = _.filter(responseData, function (item) {
                        return item.name.toLowerCase().indexOf(query.toLowerCase()) > -1;
                    });
                    callback.call(this, responseData);
                });
            }
        });

    } else {
        $('.reply-textarea').mentionsInput({
//            defaultValue  : '@[Peter Jones](id:1)', 
            onDataRequest: function (mode, query, callback) {
                $.getJSON(APP_URL + "usersList", function (responseData) {
                    responseData = _.filter(responseData, function (item) {
                        return item.name.toLowerCase().indexOf(query.toLowerCase()) > -1;
                    });
                    callback.call(this, responseData);
                });
            }
        });

}
}


function activateCommentMentions(type, post_comment) {
    if (type == 1) {
        $('.post-textarea').mentionsInput({
            defaultValue: post_comment, // '@[Dev](contact:22)',
            onDataRequest: function (mode, query, callback) {
                $.getJSON(APP_URL + "usersList", function (responseData) {
                    responseData = _.filter(responseData, function (item) {
                        return item.name.toLowerCase().indexOf(query.toLowerCase()) > -1;
                    });
                    callback.call(this, responseData);
                });
            }
        });

    } else {
        $('.post-textarea').mentionsInput({
//            defaultValue  : '@[Peter Jones](contact:200)', 
            onDataRequest: function (mode, query, callback) {
                $.getJSON(APP_URL + "usersList", function (responseData) {
                    responseData = _.filter(responseData, function (item) {
                        return item.name.toLowerCase().indexOf(query.toLowerCase()) > -1;
                    });
                    callback.call(this, responseData);
                });
            }
        });

    }

    $("#comment_input_" + reply_id).focus();
}

function discussionImageView(img_url) {
    $("#popup_mask").removeClass('hide');
    $("#discussion_image_view").removeClass('hide');
    $("#discussion_image").prop('src', img_url);

}
function loadFavoritedUsers(id,type)
{
    $.ajax({
        url: APP_URL + "getFavoriteUsers",
        data: {id: id, type: type},
        type: 'post',
        dataType: 'json',
//            processData: false,
//            contentType: false,
        success: function (response) {
            //console.log(response);
            if (response.http_status == 200) {
                if(response.users.length>0)
                {
                var i=1;
                var text="";
                //console.log(response.users.length);
                $.each(response.users, function(index, object) {
                text+="<tr><td>"+object['name']+"</td></tr>";
                i++;
            });
            var title="";
            if(type==1)
            {
                title="Users who like this";
            }
            else if(type==2)
            {
                title="Users who like this";
            }
            else
            {
                title="Users who like this";
            }
            $("#fav_users_title").text(title);
            $("#fav_users_data").html(text);
            $("#popup_mask").removeClass('hide');
                $("#favs_pops").removeClass('hide');
            
            
            }
            
 

                
            }
            if (response.http_status == 203) {
                
            }
            if (response.http_status == 440) {
                window.location = APP_URL + "/";
            }


        },
        error: function (data) {
            var json = jQuery.parseJSON(data['responseText'])
            if (json['http_status'] == 440) {
                window.location = APP_URL + "/";
            }

            
            setTimeout(function () {
                
            }, 10000);
        }

    });
}


