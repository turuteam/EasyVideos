<div class="wrap">

    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <?php

        if(isset($_POST) && $_POST['easy-video-custom-message']!='')
        {
            if(trim($_POST['easy-video-channel-id'])!='')
            update_option('EASY_VIDEOS_YOUTUBE_CHANNEL_ID',$_POST['easy-video-channel-id']);


            if(trim($_POST['easy-video-api-key'])!='')
            update_option('EASY_VIDEOS_YOUTUBE_API_KEY',$_POST['easy-video-api-key']);

        }

        // get settings keys

        $youtubeapikey= get_option('EASY_VIDEOS_YOUTUBE_API_KEY');
        $youtubechannelid= get_option('EASY_VIDEOS_YOUTUBE_CHANNEL_ID');

    ?>

    <form method="post" action="<?php echo esc_html( admin_url( 'admin.php?page=easy-videos' ) ); ?>">

        <div id="easy-videos-container">
            <p>Easy Videos - Settings - Add/ Update Youtube Channel ID & Youtube V3 Data API Key.</p>
            <p>You can get the API from here: <a href="https://console.cloud.google.com/apis/api/youtube.googleapis.com/">Youtube API Data V3</a> </p>

            <div class="options">
                <p>
                    <label><strong>Channel ID:</strong></label>
                    <br />
                    <input type="text" name="easy-video-channel-id" value="<?php echo  $youtubechannelid;?>" />
                </p>
            </div>

            <div class="options">
                <p>
                    <label><strong>Yotube API Key:</strong></label>
                    <br />
                    <input type="text" name="easy-video-api-key" value="<?php echo  $youtubeapikey;?>" />
                </p>
            </div>

        </div><!-- #easy-videos-container -->

        <?php
			wp_nonce_field( 'easy-video-settings-save', 'easy-video-custom-message' );
			submit_button();
		?>

    </form>

</div><!-- .wrap -->
<hr>
<div class="wrap">
    <style>
    .lds-facebook {
        display: inline-block;
        position: relative;
        width: 80px;
        height: 80px;
    }

    .lds-facebook div {
        display: inline-block;
        position: absolute;
        left: 8px;
        width: 16px;
        background: green;
        animation: lds-facebook 1.2s cubic-bezier(0, 0.5, 0.5, 1) infinite;
    }

    .lds-facebook div:nth-child(1) {
        left: 8px;
        animation-delay: -0.24s;
    }

    .lds-facebook div:nth-child(2) {
        left: 32px;
        animation-delay: -0.12s;
    }

    .lds-facebook div:nth-child(3) {
        left: 56px;
        animation-delay: 0;
    }

    @keyframes lds-facebook {
        0% {
            top: 8px;
            height: 64px;
        }

        50%,
        100% {
            top: 24px;
            height: 32px;
        }
    }
    </style>
    <h1>Fetch Youtube Videos</h1>
    <p class="submit">
        <input type="button" name="fetchvideos" id="fetchvideos" class="button button-primary" value="Fetch Videos">
    </p>

    <div id="css_loader">

        <div class="lds-facebook" id="loading" style="display:none;">
            <div></div>
            <div></div>
            <div></div>
        </div>
        <span id="fetch_videos_message"></span>
    </div>
</div>

<script type="text/javascript">
      jQuery(document).ready(function(){
        jQuery("#fetchvideos").on('click',function(event){
          event.preventDefault();
          
          jQuery('#loading').show();
          
          easy_videos_fetch_videos();
          
          return false;
        });
      });
      </script>