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

  // Instant Search Functionality
  let searchTimeout;
  let isSearchActive = false;
  let currentSearchTerm = '';

  $('.mp3-search-input').on('input', function() {
    clearTimeout(searchTimeout);
    const searchTerm = $(this).val().toLowerCase().trim();
    currentSearchTerm = searchTerm;
    
    searchTimeout = setTimeout(function() {
      if (searchTerm === '') {
        // Reset to normal view
        isSearchActive = false;
        $('.mp3-list li').show();
        $('.load-more-button').show();
        return;
      }

      isSearchActive = true;
      
      // Get all available titles from the hidden container
      const allTitles = $('.mp3-all-items .mp3-search-item').map(function() {
        return $(this).data('title').toLowerCase();
      }).get();

      // Find matching titles
      const matchingTitles = allTitles.filter(title => title.includes(searchTerm));

      // Hide/show items based on search
      $('.mp3-list li').each(function() {
        const title = $(this).find('.mp3-title').text().toLowerCase();
        const matches = matchingTitles.includes(title);
        $(this).toggle(matches);
        
        // Highlight matching text
        if (matches && searchTerm) {
          const regex = new RegExp('(' + searchTerm + ')', 'gi');
          const highlightedTitle = $(this).find('.mp3-title').text()
            .replace(regex, '<span class="highlight">$1</span>');
          $(this).find('.mp3-title').html(highlightedTitle);
        } else {
          $(this).find('.mp3-title').html($(this).find('.mp3-title').text());
        }
      });

      // If there are hidden matches, load more items
      if (matchingTitles.length > $('.mp3-list li:visible').length) {
        loadMoreItems(true);
      }

      // Hide load more button during search
      $('.load-more-button').toggle(!isSearchActive);
    }, 300);
  });

  // Load More Functionality
  function loadMoreItems(isSearching = false) {
    const button = $('.load-more-button');
    const page = button.data('page');
    const posts_per_page = button.data('posts-per-page') || 10;
    const playlist = button.data('playlist') || '';
    const nonce = button.data('nonce');

    // Add loading state
    button.prop('disabled', true).addClass('is-loading');

    $.ajax({
      url: mp3_frontend_params.ajax_url,
      type: 'POST',
      data: {
        action: 'mp3_load_more_tracks',
        page: page,
        posts_per_page: posts_per_page,
        playlist: playlist,
        nonce: nonce
      },
      success: function(response) {
        // Remove loading state
        button.prop('disabled', false).removeClass('is-loading');

        if (response.success === false) {
          console.error('Error loading more items:', response.data);
          return;
        }
        
        // Append the new items
        $('.mp3-list').append(response.data);
        
        // Update the page number
        button.data('page', page + 1);

        // Re-bind share button events for the newly loaded items
        bindShareButtonEvents();

        // If we're searching, immediately filter the new items
        if (isSearching && currentSearchTerm) {
          const searchTerm = currentSearchTerm;
          $('.mp3-list li').each(function() {
            const title = $(this).find('.mp3-title').text().toLowerCase();
            const matches = title.includes(searchTerm);
            $(this).toggle(matches);
            
            if (matches && searchTerm) {
              const regex = new RegExp('(' + searchTerm + ')', 'gi');
              const highlightedTitle = $(this).find('.mp3-title').text()
                .replace(regex, '<span class="highlight">$1</span>');
              $(this).find('.mp3-title').html(highlightedTitle);
            }
          });
        }

        // Hide the button if there are no more posts to load
        if (response.data.trim() === '' || isSearchActive) {
          button.hide();
        }
      },
      error: function(xhr, status, error) {
        // Remove loading state on error
        button.prop('disabled', false).removeClass('is-loading');
        console.error('Load more error:', status, error);
      }
    });
  }

  // Bind load more button click
  $('.load-more-button').on('click', function() {
    loadMoreItems();
  });
});
