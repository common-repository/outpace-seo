(function ($) {
  /**
   * oiutpaceseo Schema
   *
   * @class OutpaceseoSchema
   */
  const OutpaceseoSchema = {
    /**
     * Initializes a oiutpaceseo Schema.
     *
     * @function init
     */
    container: "",

    init() {
      const self = this;

      self.container = $("#outpaceseo-schema-settings, #outpaceseo-custom-fields");

      // Init backgrounds.
      $(document).ready(function () {
        $(".select2-class").select2();
        const selectOption = ["Site Meta", "Post Meta (Basic Fields)"];
        const customOptionGroup = $("#outpaceseo-schema-type").val();
        if ("custom-markup" === customOptionGroup) {
          for (let i = 0; i < selectOption.length; i++) {
            $('#outpaceseo-custom-markup-custom-markup optgroup[label="' + selectOption[i] + '"]').remove();
          }
        }
        const customMarkupSchemId = $("#custom-schema-schema-field").val();
        if (customMarkupSchemId) {
          for (let i = 0; i < selectOption.length; i++) {
            $("#custom-markup-" + customMarkupSchemId + '-custom-markup-connected optgroup[label="' + selectOption[i] + '"]').remove();
          }
        }
      });

      self.container.on("change", "select.outpaceseo-schema-meta-field", function () {
        const selfFun = $(this),
          parent = selfFun.parent(),
          value = selfFun.val();

        const textwrapperCustom = parent.find(".outpaceseo-schema-custom-text-wrap");
        if ("custom-text" === value) {
          textwrapperCustom.removeClass("op-hidden");
        } else if (!textwrapperCustom.hasClass("op-hidden")) {
          textwrapperCustom.addClass("op-hidden");
        }

        const textWrapperFixed = parent.find(".outpaceseo-schema-fixed-text-wrap");
        if ("fixed-text" === value) {
          textWrapperFixed.removeClass("op-hidden");
        } else if (!textWrapperFixed.hasClass("op-hidden")) {
          textWrapperFixed.addClass("op-hidden");
        }

        const specificMetaWrapper = parent.find(".outpaceseo-schema-specific-field-wrap");
        if ("specific-field" === value) {
          specificMetaWrapper.removeClass("op-hidden");
        } else if (!specificMetaWrapper.hasClass("op-hidden")) {
          specificMetaWrapper.addClass("op-hidden");
        }
      });

      self.container.on("change", ".outpaceseo-schema-row-rating-type select.outpaceseo-schema-meta-field", function (e) {
        e.preventDefault();

        $(this).closest(".outpaceseo-schema-table").find(".outpaceseo-schema-row").css("display", "");
        if ("accept-user-rating" === $(this).val()) {
          const reviewCountWrap = $(this).closest(".outpaceseo-schema-row").next(".outpaceseo-schema-row"),
            name = reviewCountWrap.find(".outpaceseo-schema-meta-field").attr("name");

          const selectedSchemaType = jQuery(".outpaceseo-review-schema-type").val();
          if (selectedSchemaType) {
            const prepareName = "outpaceseo-review[" + selectedSchemaType + "-review-count]";

            if (name.indexOf(prepareName) >= 0) {
              reviewCountWrap.hide();
            }
          }

          if (name.indexOf("[review-count]") >= 0) {
            reviewCountWrap.hide();
          }
        }
      });
      self.container.find("select.outpaceseo-schema-meta-field").trigger("change");

      $("select.outpaceseo-schema-select2").each(function (index, el) {
        self.init_target_rule_select2(el);
      });

      self.container.on("click", ".op-repeater-add-new-btn", function (event) {
        event.preventDefault();
        self.add_new_repeater($(this));
        self.prepare_event_schmea_fields();
      });

      self.container.on("click", ".op-repeater-close", function (event) {
        event.preventDefault();
        self.add_remove_repeater($(this));
      });

      self.schemaTypeDependency();
      self.bindTooltip();
      if (!$("body").hasClass("post-type-outpace_schema")) {
        self.field_validation();
      }
    },
    field_validation() {
      $(".outpaceseo-schema-meta-field, image-field-wrap, .outpaceseo-custom-field, .wpsp-custom-field-connect").on("click focus change", function () {
        $(".outpaceseo-schema-type-wrap").each(function (index, repeater) {
          let fieldValue = $(repeater).find(".op-default-hidden-value").val();
          const requiredPath = $(repeater).parents(".outpaceseo-schema-row-content").prev();
          if (undefined !== fieldValue) {
            fieldValue = fieldValue.trim();
            if (fieldValue) {
              if ($("body").hasClass("block-editor-page")) {
                if (!$(repeater).find(".op-required-error-field").length) {
                  let metaField;
                  switch (fieldValue) {
                    case "post_title":
                      metaField = $(".editor-post-title__input").val();
                      break;
                    case "post_content":
                      metaField = $("p.block-editor-rich-text__editable").text().length > 1 ? $("p.block-editor-rich-text__editable").text() : "";
                      break;
                    case "post_excerpt":
                      metaField = $(".components-textarea-control__input").val();
                      break;
                    case "featured_img":
                      if ("Set featured image" === $(".editor-post-featured-image__toggle").text()) {
                        metaField = "";
                      } else {
                        metaField = $(".components-responsive-wrapper__content").attr("src");
                      }
                      break;
                    default:
                      requiredPath.removeClass("op-required-error-field");
                      requiredPath.find("label").removeClass("op-required-error-field");
                  }

                  if (undefined !== metaField) {
                    if ("" !== metaField) {
                      requiredPath.removeClass("op-required-error-field");
                      requiredPath.find("label").removeClass("op-required-error-field");
                    } else if (requiredPath.find(".required").length) {
                      requiredPath.find("label").addClass("op-required-error-field");
                    }
                  }
                } else {
                  requiredPath.removeClass("op-required-error-field");
                  requiredPath.find("label").removeClass("op-required-error-field");
                }
              } else {
                requiredPath.removeClass("op-required-error-field");
                requiredPath.find("label").removeClass("op-required-error-field");
              }
            } else if (requiredPath.find(".required").length) {
              requiredPath.find("label").addClass("op-required-error-field");
            }
          }
        });
      });
    },
    hide_review_count() {
      $(this).closest(".outpaceseo-schema-table").find(".outpaceseo-schema-row").css("display", "");
      if ("accept-user-rating" === $(this).val()) {
        const reviewCountWrap = $(this).closest(".outpaceseo-schema-row").next(".outpaceseo-schema-row"),
          name = reviewCountWrap.find(".outpaceseo-schema-meta-field").attr("name");

        const selectedSchemaType = jQuery(".outpaceseo-review-schema-type").val();
        if (selectedSchemaType) {
          const prepareName = "outpaceseo-review[" + selectedSchemaType + "-review-count]";

          if (name.indexOf(prepareName) >= 0) {
            reviewCountWrap.hide();
          }
        }

        if (name.indexOf("[review-count]") >= 0) {
          reviewCountWrap.hide();
        }
      }
    },

    add_new_repeater(selector) {
      const self = this,
        parentWrap = selector.closest(".outpaceseo-schema-type-wrap"),
        totalCount = parentWrap.find(".outpaceseo-repeater-table-wrap").length,
        template = parentWrap.find(".outpaceseo-repeater-table-wrap").first().clone();

      template.find(".outpaceseo-schema-custom-text-wrap, .outpaceseo-schema-specific-field-wrap").each(function () {
        if (!$(this).hasClass("op-hidden")) {
          $(this).addClass("op-hidden");
        }
      });

      template.find("select.outpaceseo-schema-meta-field").each(function () {
        $(this).val("none");

        const fieldName =
            "undefined" !== typeof $(this).attr("name")
              ? $(this)
                  .attr("name")
                  .replace("[0]", "[" + totalCount + "]")
              : "",
          fieldClass =
            "undefined" !== typeof $(this).attr("class")
              ? $(this)
                  .attr("class")
                  .replace("-0-", "-" + totalCount + "-")
              : "",
          fieldId =
            "undefined" !== typeof $(this).attr("id")
              ? $(this)
                  .attr("id")
                  .replace("-0-", "-" + totalCount + "-")
              : "";

        $(this).attr("name", fieldName);
        $(this).attr("class", fieldClass);
        $(this).attr("id", fieldId);
      });
      template.find("input, textarea, select:not(.outpaceseo-schema-meta-field)").each(function () {
        $(this).val("");

        const fieldName =
            "undefined" !== typeof $(this).attr("name")
              ? $(this)
                  .attr("name")
                  .replace("[0]", "[" + totalCount + "]")
              : "",
          fieldClass =
            "undefined" !== typeof $(this).attr("class")
              ? $(this)
                  .attr("class")
                  .replace("-0-", "-" + totalCount + "-")
              : "",
          fieldId =
            "undefined" !== typeof $(this).attr("id")
              ? $(this)
                  .attr("id")
                  .replace("-0-", "-" + totalCount + "-")
              : "";

        $(this).attr("name", fieldName);
        $(this).attr("class", fieldClass);
        $(this).attr("id", fieldId);
      });

      template.find("span.select2-container").each(function () {
        $(this).remove();
      });

      template.insertBefore(selector);
      template.find("select.outpaceseo-schema-select2").each(function (index, el) {
        self.init_target_rule_select2(el);
      });

      OutpaceseoSchema.init_date_time_fields();
    },

    add_remove_repeater(selector) {
      const parentWrap = selector.closest(".outpaceseo-schema-type-wrap"),
        repeaterCount = parentWrap.find("> .outpaceseo-repeater-table-wrap").length;

      if (repeaterCount > 1) {
        selector.closest(".outpaceseo-repeater-table-wrap").remove();

        if ("outpaceseo-custom-fields" === this.container.attr("id")) {
          parentWrap.find("> .outpaceseo-repeater-table-wrap").each(function (wrapIndex, repeaterWap) {
            $(repeaterWap).each(function (elementIndex, element) {
              $(element)
                .find("input, textarea, select:not(.outpaceseo-schema-meta-field)")
                .each(function (elIndex, el) {
                  const fieldName =
                    "undefined" !== typeof $(el).attr("name")
                      ? $(el)
                          .attr("name")
                          .replace(/\[\d+]/, "[" + wrapIndex + "]")
                      : "";
                  $(el).attr("name", fieldName);
                });
            });
          });
        }
      }
    },

    bindTooltip() {
      // Call Tooltip
      $(".outpaceseo-schema-heading-help").tooltip({
        content() {
          return $(this).prop("title");
        },
        tooltipClass: "outpaceseo-schema-ui-tooltip",
        position: {
          my: "center top",
          at: "center bottom+10",
        },
        hide: {
          duration: 200,
        },
        show: {
          duration: 200,
        },
      });
    },

    schemaTypeDependency() {
      const container = this.container;
      this.container.on("change", 'select[name="outpaceseo-schema-type"]', function () {
        container.find(".outpaceseo-schema-meta-wrap").css("display", "none");
        const schemaType = $(this).val();
        if ("undefined" !== typeof schemaType && "" !== schemaType) {
          container.find("#op-" + schemaType + "-schema-meta-wrap").css("display", "");
        }
      });
    },

    init_target_rule_select2(selector) {
      $(selector).select2({
        placeholder: "Search Fields...",
        ajax: {
          url: ajaxurl,
          dataType: "json",
          method: "post",
          delay: 250,
          data(params) {
            return {
              nonce_ajax: AIOSRS_Rating.specified_field,
              q: params.term,
              page: params.page,
              action: "op_get_specific_meta_fields",
            };
          },
          processResults(data) {
            return {
              results: data,
            };
          },
          cache: true,
        },
        minimumInputLength: 2,
      });
    },

    get_review_item_type_html(itemType) {
      jQuery
        .post({
          url: ajaxurl,
          data: {
            action: "fetch_item_type_html",
            itemType,
            nonce: AIOSRS_Rating.security,
            post_id: jQuery("#post_ID").val(),
          },
        })
        .done(function (response) {
          $(".op-review-item-type-field").remove();
          $(response).insertAfter(jQuery("#outpaceseo-review-schema-type").parent().parent().closest("tr"));
          $("select.outpaceseo-schema-select2").each(function (index, el) {
            OutpaceseoSchema.init_target_rule_select2(el);
          });

          const itemSpecificType = ".outpaceseo-review-" + itemType + "-rating";
          $(itemSpecificType).each(function () {
            $(this).closest(".outpaceseo-schema-table").find(".outpaceseo-schema-row").css("display", "");
            if ("accept-user-rating" === $(this).val()) {
              const reviewCountWrap = $(this).closest(".outpaceseo-schema-row").next(".outpaceseo-schema-row"),
                name = reviewCountWrap.find(".outpaceseo-schema-meta-field").attr("name");

              const selectedSchemaType = jQuery(".outpaceseo-review-schema-type").val();
              if (selectedSchemaType) {
                const prepareName = "outpaceseo-review[" + selectedSchemaType + "-review-count]";

                if (name.indexOf(prepareName) >= 0) {
                  reviewCountWrap.hide();
                }
              }

              if (name.indexOf("[review-count]") >= 0) {
                reviewCountWrap.hide();
              }
            }
          });

          OutpaceseoSchema.init_date_time_fields();
          OutpaceseoSchema.prepare_event_schmea_fields();
        })
        .fail(function () {});
    },

    prepare_event_schmea_fields() {
      $(".wpsp-dropdown-event-status, .wpsp-dropdown-outpaceseo-event-event-status").change(function () {
        const parent = $(this).parents(".outpaceseo-schema-meta-wrap, .outpaceseo-meta-fields-wrap");

        parent.find("td.wpsp-event-status-rescheduled, td.outpaceseo-review-outpaceseo-event-previous-date").hide();
        if (!this.value) {
          this.value = "EventScheduled";
        }

        if ("EventRescheduled" === this.value) {
          parent.find("td.wpsp-event-status-rescheduled, td.outpaceseo-review-outpaceseo-event-previous-date").show();
        }

        const eventStatus = $(".wpsp-dropdown-event-attendance-mode, .wpsp-dropdown-outpaceseo-event-event-attendance-mode").val();

        if ("EventMovedOnline" === this.value || "OfflineEventAttendanceMode" !== eventStatus) {
          parent.find("td.wpsp-event-status-offline").hide();
          parent.find("td.wpsp-event-status-online").show();
          parent.find(".wpsp-dropdown-event-attendance-mode, .wpsp-dropdown-outpaceseo-event-event-attendance-mode").val("OnlineEventAttendanceMode");
        } else {
          parent.find("td.wpsp-event-status-offline").show();
          parent.find("td.wpsp-event-status-online").hide();
        }
      });
      $(".wpsp-dropdown-event-attendance-mode, .wpsp-dropdown-outpaceseo-event-event-attendance-mode").change(function () {
        const parent = $(this).parents(".outpaceseo-schema-meta-wrap, .outpaceseo-meta-fields-wrap");
        parent.find("td.wpsp-event-status-rescheduled").hide();
        const eventStatus = $(".wpsp-dropdown-event-status, .wpsp-dropdown-outpaceseo-event-event-status").val();

        if ("EventMovedOnline" !== eventStatus) {
          parent.find("td.wpsp-event-status-offline").show();
          parent.find("td.wpsp-event-status-online").hide();
        }

        if ("OfflineEventAttendanceMode" !== this.value) {
          parent.find("td.wpsp-event-status-offline").hide();
          parent.find("td.wpsp-event-status-online").show();
        }

        if ("MixedEventAttendanceMode" === this.value) {
          parent.find("td.wpsp-event-status-offline").show();
          parent.find("td.wpsp-event-status-online").show();
        }
      });

      $(".wpsp-dropdown-event-attendance-mode, .wpsp-dropdown-outpaceseo-event-event-attendance-mode").trigger("change");
    },

    init_date_time_fields() {
      $(".wpsp-datetime-local-field, .wpsp-date-field, .wpsp-time-duration-field").each(function () {
        $(this).removeClass("hasDatepicker");
      });

      const startDateSelectors = ".wpsp-date-published-date, .wpsp-datetime-local-event-start-date, .wpsp-date-start-date, .wpsp-datetime-local-start-date";
      const endDateSelectors = ".wpsp-date-modified-date, .wpsp-datetime-local-event-end-date, .wpsp-date-end-date, .wpsp-datetime-local-end-date";

      $(document).on("focus", ".wpsp-time-duration-field", function () {
        $(this).timepicker({
          timeFormat: "HH:mm:ss",
          hourMin: 0,
          hourMax: 99,
          oneLine: true,
          currentText: "Clear",
          onSelect() {
            updateTimeFormat(this);
          },
        });
      });

      $(document).on("focus", ".wpsp-datetime-local-field, .wpsp-date-field", function () {
        $(this).datetimepicker({
          dateFormat: "yy-mm-dd",
          timeFormat: "hh:mm TT",
          changeMonth: true,
          changeYear: true,
          showOn: "focus",
          showButtonPanel: true,
          closeText: "Done",
          currentText: "Clear",
          yearRange: "-100:+10", // last hundred year
          onClose(dateText, inst) {
            const thisEle = "#" + inst.id;
            if (jQuery(thisEle).is(startDateSelectors)) {
              $(endDateSelectors).datetimepicker("option", "minDate", new Date(dateText));
            } else if (jQuery(thisEle).is(endDateSelectors)) {
              $(startDateSelectors).datetimepicker("option", "maxDate", new Date(dateText));
            }
            jQuery(thisEle).parents(".wpsp-local-fields").find(".wpsp-default-hidden-value").val(dateText);
          },
        });
      });

      $.datepicker._gotoToday = function (id) {
        $(id).datepicker("setDate", "").datepicker("hide").blur();
      };

      function updateTimeFormat(thisEle) {
        const durationWrap = $(thisEle).closest(".outpaceseo-custom-field-time-duration");
        const inputField = durationWrap.find(".time-duration-field");
        let value = $(thisEle).val();
        value = value.replace(/:/, "H");
        value = value.replace(/:/, "M");
        value = "PT" + value + "S";
        inputField.val(value);

        // Post/pages related support.
        const parent = $(thisEle).parents(".wpsp-local-fields");
        parent.find(".wpsp-default-hidden-value").val(value);
      }
    },
  };

  $(function () {
    OutpaceseoSchema.init();

    if (!$("body").hasClass("outpaceseo-setup")) {
      OutpaceseoSchema.init_date_time_fields();
    }
  });

  $(document).ready(function () {
    let parent = $(".outpaceseo-meta-fields-wrap");
    parent.each(function (index, value) {
      let labelMarkup = $(value).find(".wpsp-field-label");
      let label = labelMarkup.text();
      if ("Image License" === label.trim()) {
        labelMarkup.attr("style", "width:6%");
      }
    });
    $("#outpaceseo-review-schema-type").change(function () {
      const itemVal = $(this).val().trim();
      if (!itemVal) {
        $(".op-review-item-type-field").remove();
        return;
      }
      OutpaceseoSchema.get_review_item_type_html(itemVal);
    });
    $("#outpaceseo-review-schema-type").change();

    OutpaceseoSchema.prepare_event_schmea_fields();
  });
})(jQuery);
