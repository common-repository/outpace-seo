/**
 * OutpaceSEO JS
 * global outpaceseo_params
 */
(function ($) {
  var outpaceseo_stop = false;
  var OutpaceSEO = {
    /**
     * Start the engine.
     *
     */
    init: function () {
      OutpaceSEO.bindUIActions();
      setTimeout(OutpaceSEO.alertFunc, 200);
    },

    /**
     * Element bindings.
     *
     */
    bindUIActions: function () {
      $(document).on("click", ".outpaceseo_run", function (e) {
        OutpaceSEO.runBluckUpdater(this, e);
      });
      $(document).on("click", ".outpaceseo_test", function (e) {
        OutpaceSEO.testBluckUpdater(this, e);
      });
      $(document).on("click", ".outpaceseo_stop", function (e) {
        OutpaceSEO.stopBluckUpdater(this, e);
      });
      $(document).on("click", ".outpaceseo_reset", function (e) {
        OutpaceSEO.resetCounter(this, e);
      });
      $(document).on("change", ".outpace_script_pagetrack select", function (e) {
        OutpaceSEO.selectVal(this, e);
      });
      $(document).on("change", ".only-specific select", function (e) {
        OutpaceSEO.selectValPagePost(this, e);
      });
      $(document).on("click", ".outpaceseo-schema-temp-wrap", function (e) {
        OutpaceSEO.clickSchema(this, e);
      });
    },

    clickSchema: function (el, e) {
      var $this = $(el);
      $(".outpaceseo-schema-temp-wrap").removeClass("selected");
      $(".outpaceseo-schema-setup-actions").find(".button-next").removeAttr("disabled");

      $this.addClass("selected");

      const type = $this.data("schema-type"),
        title = $this.data("schema-title");

      $(document).find(".outpaceseo-schema-type").val(type);
      $(document).find(".outpaceseo-schema-title").val(title);
    },

    selectVal: function (el, e) {
      var $this = $(el);
      var value = $this.val();
      var type = $(".only-specific select").val();
      if (value == "specific") {
        $(".only-specific").show();
        if (type == "page") {
          $(".posts").hide();
          $(".pages").show();
        } else if (type == "post") {
          $(".pages").hide();
          $(".posts").show();
        }
      } else {
        $(".only-specific").hide();
        $(".posts").hide();
        $(".pages").hide();
      }
    },

    selectValPagePost: function (el, e) {
      var $this = $(el);
      var value = $this.val();
      var pageTrack = $(".outpace_script_pagetrack select").val();
      if (pageTrack == "specific" && value == "page") {
        $(".pages").show();
        $(".posts").hide();
      } else if (pageTrack == "specific" && value == "post") {
        $(".posts").show();
        $(".pages").hide();
      }
    },

    alertFunc: function () {
      var text;
      text = $("#codeAce").html();
      text = OutpaceSEO.RoshanReplace(text, "&lt;", "<");
      text = OutpaceSEO.RoshanReplace(text, "&gt;", ">");
      text = OutpaceSEO.RoshanReplace(text, "&amp;", "&");

      var editor = ace.edit("codeAce");
      editor.renderer.setShowGutter(false);
      editor.setTheme("ace/theme/monokai");
      editor.getSession().setMode("ace/mode/html");
      editor.getSession().setUseSoftTabs(true);
      editor.getSession().setUseWrapMode(true);
      editor.session.setUseWorker(false);
      editor.setValue(text);
      $("#codeAce").focusout(function () {
        var $hidden = $("#code");
        var code = editor.getValue();
        $hidden.val(code);
      });
      $("#codeAce").trigger("focusout");
    },

    RoshanReplace: function (target, search, replacement) {
      try {
        return target.split(search).join(replacement);
      } catch (e) {
        if (e) {
          return target;
        }
      }
    },

    runBluckUpdater: function (el, e) {
      $.confirm({
        title: false,
        theme: "modern",
        content: outpaceseo_params.run_text,
        backgroundDismiss: false,
        boxWidth: "500px",
        useBootstrap: false,
        closeIcon: false,
        icon: "dashicons dashicons-info",
        type: "orange",
        buttons: {
          confirm: {
            text: outpaceseo_params.i18n_outpaceseo_ok,
            btnClass: "btn-confirm",
            keys: ["enter"],
            action: function () {
              OutpaceSEO.outpaceseoBulkUpdater();
            },
          },
          cancel: {
            text: outpaceseo_params.i18n_outpaceseo_cancel,
          },
        },
      });
    },

    testBluckUpdater: function (el, e) {
      $.confirm({
        title: false,
        theme: "modern",
        content: outpaceseo_params.test_text,
        backgroundDismiss: false,
        boxWidth: "500px",
        useBootstrap: false,
        closeIcon: false,
        icon: "dashicons dashicons-info",
        type: "orange",
        buttons: {
          confirm: {
            text: outpaceseo_params.i18n_outpaceseo_ok,
            btnClass: "btn-confirm",
            keys: ["enter"],
            action: function () {
              OutpaceSEO.outpaceseoBulkUpdater(true);
            },
          },
          cancel: {
            text: outpaceseo_params.i18n_outpaceseo_cancel,
          },
        },
      });
    },

    stopBluckUpdater: function () {
      outpaceseo_stop = true;
    },

    resetCounter: function () {
      $.confirm({
        title: false,
        theme: "modern",
        content: outpaceseo_params.reset_text,
        backgroundDismiss: false,
        boxWidth: "500px",
        useBootstrap: false,
        closeIcon: false,
        icon: "dashicons dashicons-info",
        type: "orange",
        buttons: {
          confirm: {
            text: outpaceseo_params.i18n_outpaceseo_ok,
            btnClass: "btn-confirm",
            keys: ["enter"],
            action: function () {
              data = {
                action: "outpaceseo_reset_bulk_updater_counter",
                security: outpaceseo_params.ajax_nonce,
              };
              $.post(ajaxurl, data, function (response) {
                $("#outpaceseo-log").append('<p class="outpaceseo-class-green"><span class="dashicons dashicons-yes"></span>' + response.message + "</p>");
                $("#outpaceseo-log").append("<p>Number of Images Remaining: " + response.remaining_images + "</p>");
                $("#outpaceseo-log").append("<p>Number of Images Updated: 0</p>");
                $("#outpaceseo-log").animate(
                  {
                    scrollTop: $("#outpaceseo-log")[0].scrollHeight - $("#outpaceseo-log").height(),
                  },
                  200
                );
              });
            },
          },
          cancel: {
            text: outpaceseo_params.i18n_outpaceseo_cancel,
          },
        },
      });
    },

    outpaceseoBulkUpdater: function (testBluck = false) {
      outpaceseo_stop = false;
      var focused = true;
      window.onfocus = function () {
        focused = true;
      };
      window.onblur = function () {
        focused = false;
      };
      $(".outpaceseo-spinner").addClass("spinner");
      $(".outpaceseo_stop").prop("disabled", false);
      $(".outpaceseo_stop").removeClass("button-secondary");
      $(".outpaceseo_stop").addClass("button-primary");

      $("#outpaceseo-log").append('<p class="outpaceseo-class-green"><span class="dashicons dashicons-controls-play"></span>Initializing bulk updater. Please be patient and do not close the browser while it\'s running. In case you do, you can always resume by returning to this page later.</p>');
      $("#outpaceseo-log").animate({ scrollTop: $("#outpaceseo-log")[0].scrollHeight - $("#outpaceseo-log").height() }, 200);
      remainingImages = null;
      data = {
        action: "outpaceseo_count_remaining_images",
      };

      var remainingImages = null;
      var remainingImageCount = $.post(ajaxurl, data, function (response) {
        remainingImages = response.data;
      });

      remainingImageCount.done(function outpaceseo_rename_image() {
        if (testBluck === true && remainingImages > 1) {
          remainingImages = 1;
        }

        if (remainingImages > 0 && outpaceseo_stop === false) {
          data = {
            action: "rename_old_image",
            security: outpaceseo_params.ajax_nonce,
          };

          var rename_image = $.post(ajaxurl, data, function (response) {
            $("#outpaceseo-log").append("<p>" + response + "</p>");
            if (testBluck === false) {
              $("#outpaceseo-log").append("<p>Images remaining: " + (remainingImages - 1) + "</p>");
            }

            if ($("#outpaceseo-log").prop("scrollHeight") - ($("#outpaceseo-log").scrollTop() + $("#outpaceseo-log").height()) < 100 || focused == false) {
              $("#outpaceseo-log").animate({ scrollTop: $("#outpaceseo-log")[0].scrollHeight - $("#outpaceseo-log").height() }, 100);
            }
          });

          rename_image.done(function () {
            remainingImages--;
            outpaceseo_rename_image();
          });
        } else {
          if (outpaceseo_stop === false) {
            $("#outpaceseo-log").append('<p class="outpaceseo-class-green"><span class="dashicons dashicons-yes"></span>All done!</p>');
            $("#outpaceseo-log").animate({ scrollTop: $("#outpaceseo-log")[0].scrollHeight - $("#outpaceseo-log").height() }, 200);
          } else {
            $("#outpaceseo-log").append('<p class="outpaceseo-class-red"><span class="dashicons dashicons-dismiss"></span>Operation aborted by user.</p>');
            $("#outpaceseo-log").animate({ scrollTop: $("#outpaceseo-log")[0].scrollHeight - $("#outpaceseo-log").height() }, 200);
          }

          $(".outpaceseo-spinner").removeClass("spinner");
          $(".outpaceseo_stop").removeClass("button-primary");
          $(".outpaceseo_stop").addClass("button-secondary");
          $(".outpaceseo_stop").prop("disabled", true);
        }
      });
    },
  };
  OutpaceSEO.init();
})(jQuery);
