function easy_videos_fetch_videos() {

    var data = {
        'action': 'easy_videos_fetch_videos'
    };

    // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
    jQuery.post(ajax_object.ajax_url, data, function(response) {

        console.log(response);
        if (response == 0)
            jQuery('#fetch_videos_message').html('Oops! Something went wrong. Please check the Yoyube ChannelID and API Key.');
        else
            jQuery('#fetch_videos_message').html(response);

        jQuery('#loading').hide();

    });
}