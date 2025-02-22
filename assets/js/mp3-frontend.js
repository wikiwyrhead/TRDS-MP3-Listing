jQuery(document).ready(function ($) {
  // MP3 Upload Functionality
  var mediaUploader;

  $(".upload-mp3-button").click(function (e) {
    e.preventDefault();
    var inputField = $(this).prev("input");

    if (mediaUploader) {
      mediaUploader.open();
      return;
    }

    mediaUploader = wp.media({
      title: "Choose MP3 File",
      button: {
        text: "Choose File",
      },
      multiple: false,
      library: {
        type: "audio/mpeg",
      },
    });

    mediaUploader.on("select", function () {
      var attachment = mediaUploader.state().get("selection").first().toJSON();
      inputField.val(attachment.url);
    });

    mediaUploader.open();
  });

  // Function to bind share button events
  function bindShareButtonEvents() {
    $(".share-button")
      .off("click")
      .on("click", function () {
        var $dropdown = $(this).next(".share-dropdown");
        $(".share-dropdown").not($dropdown).hide(); // Hide other dropdowns
        $dropdown.toggle(); // Toggle the current dropdown
      });
  }

  // Bind share button events on initial load
  bindShareButtonEvents();

  // Close dropdowns when clicking outside
  $(document).on("click", function (e) {
    if (!$(e.target).closest(".share-button, .share-dropdown").length) {
      $(".share-dropdown").hide(); // Hide all dropdowns
    }
  });

  // Play Count Functionality
  $("audio.mp3-audio").on("play", function () {
    var mp3_id = $(this).data("mp3-id");
    var nonce = $(this).data("nonce");
    $.ajax({
      url: mp3_ajax_params.ajax_url,
      type: "POST",
      data: {
        action: "mp3_update_play_count",
        mp3_id: mp3_id,
        nonce: nonce
      },
      error: function(xhr, status, error) {
        console.error('Play count update error:', status, error);
      }
    });
  });

  // Load More Functionality
  $(".load-more-button").on("click", function () {
    var button = $(this);
    var page = button.data("page");
    var nonce = button.data("nonce");
    var posts_per_page = 10; // Match the posts_per_page in the shortcode

    // Add loading state
    button.prop('disabled', true).addClass('is-loading');

    $.ajax({
      url: mp3_ajax_params.ajax_url,
      type: "POST",
      data: {
        action: "mp3_load_more_tracks", // Updated action name to match PHP function
        page: page,
        posts_per_page: posts_per_page,
        nonce: nonce
      },
      success: function (response) {
        // Remove loading state
        button.prop('disabled', false).removeClass('is-loading');

        if (response.success === false) {
          console.error('Error loading more items:', response.data);
          return;
        }
        
        // Append the new items
        $(".mp3-list").append(response.data);
        
        // Update the page number
        button.data("page", page + 1);

        // Re-bind share button events for the newly loaded items
        bindShareButtonEvents();

        // Hide the button if there are no more posts to load
        if (response.data.trim() === "") {
          button.hide();
        }
      },
      error: function(xhr, status, error) {
        // Remove loading state on error
        button.prop('disabled', false).removeClass('is-loading');
        console.error('Load more error:', status, error);
      }
    });
  });
});
