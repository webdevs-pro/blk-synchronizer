jQuery(document).ready(function($) {


   $('#blk-start-import').click(function(e) {
      e.preventDefault();

      var $startButton = $(this);
      $startButton.addClass('disabled');

      $.ajax({
         url: '/?action=blkSynchronizer&method=synchronize',
         type: 'POST',
         success: function(data) {
               console.log('import complete'); // Handle the response data
               $startButton.removeClass('disabled');
         },
         error: function(jqXHR, textStatus, errorThrown) {
               console.error('There has been a problem with your AJAX operation:', textStatus, errorThrown);
               $startButton.removeClass('disabled');
               // Handle errors here
         }
      });
   });


   $('#blk-stop-import').click(function (e) {
      e.preventDefault();

      var data = {
            action: 'blk_stop_import',
      };

      $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            beforeSend: function (xhr) {
               $('#blk-stop-import').addClass('disabled');
               //  $('#frymo_ajax_result').html('&nbsp;');
            },
            success: function (response) {
               $('#blk-stop-import').removeClass('disabled');
               //  $('#frymo_ajax_result').html(response);
               console.log(response);
            }
      });
   });


   // Function to load log file content
   function loadLogFile(filePath) {

      $('#blk-log-file-select').prop('disabled', true);
      $('#blk-log-reload').addClass('disabled');
      $('#blk-log-reload').blur();

      // Append a timestamp to the file path to prevent caching
      var cacheBuster = "?t=" + new Date().getTime();
      $.get(filePath + cacheBuster, function(response) {
      
      
         // Use 'plaintext' for generic highlighting without specific syntax rules
         var new_code = Prism.highlight(response, Prism.languages.log, 'log');
      
      
         // Ensure your <pre> and <code> tags use a language class that matches what you've passed to Prism.highlight
         $('#blk-log').html(`<pre class="language-log"><code>${new_code}</code></pre>`);
         $('#blk-log pre').scrollTop($('#blk-log pre')[0].scrollHeight)
      });

      setTimeout(function() {
         $('#blk-log-file-select').prop('disabled', false);
         $('#blk-log-reload').removeClass('disabled');
      }, 500);
   }

   var currentFile = $('#blk-log-file-select').val();
   var logFilePath = blkAdminPageData.logFilePath + currentFile;

   loadLogFile(logFilePath);

   // Handle change event for the log file selection
   $('#blk-log-file-select').change(function() {
      var selectedFile = $(this).val();
      var logFilePath = blkAdminPageData.logFilePath + selectedFile;
      loadLogFile(logFilePath);
   });

   // Handle click event for the "Reload" link
   $('#blk-log-reload').click(function(e) {
      e.preventDefault();
      var currentFile = $('#blk-log-file-select').val();
      var logFilePath = blkAdminPageData.logFilePath + currentFile;
      loadLogFile(logFilePath);
   });


});