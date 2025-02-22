jQuery(document).ready(function($) {
    // Initialize sortable
    $('.track-list').sortable({
        handle: '.track-handle',
        update: function(event, ui) {
            var trackIds = [];
            $('.track-item').each(function() {
                trackIds.push($(this).data('track-id'));
            });
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mp3_update_track_order',
                    track_ids: trackIds,
                    nonce: $('#mp3_track_order_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Track order updated successfully');
                    }
                }
            });
        }
    });

    // Load more tracks
    $('.load-more-button').on('click', function() {
        var button = $(this);
        var page = parseInt(button.data('page'));
        var playlistId = button.data('playlist-id');

        // Disable button and show loading state
        button.prop('disabled', true).addClass('is-loading');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mp3_load_more_tracks',
                page: page,
                playlist_id: playlistId,
                is_admin: true,
                nonce: button.data('nonce')
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Append new tracks
                    button.closest('.mp3-playlist-section').find('.track-list').append(response.data);
                    button.data('page', page + 1);
                    
                    // If no more tracks, hide the button
                    if (response.data.trim() === '') {
                        button.hide();
                    }
                }
            },
            complete: function() {
                // Re-enable button and remove loading state
                button.prop('disabled', false).removeClass('is-loading');
            }
        });
    });

    // Remove track from playlist
    $(document).on('click', '.remove-track', function() {
        var button = $(this);
        var trackId = button.data('track-id');
        var playlistId = button.closest('.track-list').data('playlist-id');

        if (confirm('Are you sure you want to remove this track from the playlist?')) {
            button.prop('disabled', true);
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mp3_remove_from_playlist',
                    track_id: trackId,
                    playlist_id: playlistId,
                    nonce: $('#mp3_playlist_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        button.closest('.track-item').fadeOut(300, function() {
                            $(this).remove();
                        });
                    }
                },
                error: function() {
                    button.prop('disabled', false);
                }
            });
        }
    });
});
