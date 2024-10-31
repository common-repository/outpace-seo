(function ($) {
  const initTargetRuleSelect2 = function (selector) {
    $(selector).select2({
      placeholder: "Search pages / post / archives",

      ajax: {
        url: ajaxurl,
        dataType: "json",
        method: "post",
        delay: 250,
        data(params) {
          return {
            q: params.term, // search term
            page: params.page,
            action: "op_get_posts_by_query",
            nonce: Targetrule.security,
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
  };

  const updateTargetRuleInput = function (wrapper) {
    const ruleInput = wrapper.find(".op-target_rule-input");
    const newValue = [];

    wrapper.find(".op-target-rule-condition").each(function () {
      const $this = $(this);
      let tempObj = {};
      const ruleCondition = $this.find("select.target_rule-condition");
      const specificPage = $this.find("select.target_rule-specific-page");

      const ruleConditionVal = ruleCondition.val();
      const specificPageVal = specificPage.val();

      if ("" !== ruleConditionVal) {
        tempObj = {
          type: ruleConditionVal,
          specific: specificPageVal,
        };

        newValue.push(tempObj);
      }
    });

    const rulesString = JSON.stringify(newValue);
    ruleInput.val(rulesString);
  };

  const updateCloseButton = function (wrapper) {
    const type = wrapper.closest(".op-target-rule-wrapper").attr("data-type");
    const rules = wrapper.find(".op-target-rule-condition");
    let showClose = false;

    if ("display" === type) {
      if (rules.length > 1) {
        showClose = true;
      }
    } else {
      showClose = true;
    }

    rules.each(function () {
      if (showClose) {
        jQuery(this).find(".target_rule-condition-delete").removeClass("op-hidden");
      } else {
        jQuery(this).find(".target_rule-condition-delete").addClass("op-hidden");
      }
    });
  };

  const updateExclusionButton = function (forceShow, forceHide) {
    const displayOn = $(".op-target-rule-display-on-wrap");
    const excludeOn = $(".op-target-rule-exclude-on-wrap");

    const excludeFieldWrap = excludeOn.closest("tr");
    const addExcludeBlock = displayOn.find(".target_rule-add-exclusion-rule");
    const excludeConditions = excludeOn.find(".op-target-rule-condition");

    if (true === forceHide) {
      excludeFieldWrap.addClass("op-hidden");
      addExcludeBlock.removeClass("op-hidden");
    } else if (true === forceShow) {
      excludeFieldWrap.removeClass("op-hidden");
      addExcludeBlock.addClass("op-hidden");
    } else if (1 === excludeConditions.length && "" === $(excludeConditions[0]).find("select.target_rule-condition").val()) {
      excludeFieldWrap.addClass("op-hidden");
      addExcludeBlock.removeClass("op-hidden");
    } else {
      excludeFieldWrap.removeClass("op-hidden");
      addExcludeBlock.addClass("op-hidden");
    }
  };

  $(document).ready(function () {
    jQuery(".op-target-rule-condition").each(function () {
      const $this = $(this),
        condition = $this.find("select.target_rule-condition"),
        conditionVal = condition.val(),
        specificPage = $this.next(".target_rule-specific-page-wrap");

      if ("specifics" === conditionVal) {
        specificPage.slideDown(300);
      }
    });

    jQuery(".op-target-rule-selector-wrapper select.target-rule-select2").each(function (index, el) {
      initTargetRuleSelect2(el);
    });

    jQuery(".op-target-rule-selector-wrapper").each(function () {
      updateCloseButton(jQuery(this));
    });

    jQuery(document).on("change", ".op-target-rule-condition select.target_rule-condition", function () {
      const $this = jQuery(this),
        thisVal = $this.val(),
        fieldWrap = $this.closest(".op-target-rule-wrapper");

      if ("specifics" === thisVal) {
        $this.closest(".op-target-rule-condition").next(".target_rule-specific-page-wrap").slideDown(300);
      } else {
        $this.closest(".op-target-rule-condition").next(".target_rule-specific-page-wrap").slideUp(300);
      }

      updateTargetRuleInput(fieldWrap);
    });

    jQuery(".op-target-rule-selector-wrapper").on("change", ".target-rule-select2", function () {
      const $this = jQuery(this),
        fieldWrap = $this.closest(".op-target-rule-wrapper");

      updateTargetRuleInput(fieldWrap);
    });

    jQuery(".op-target-rule-selector-wrapper").on("click", ".target_rule-add-rule-wrap a", function (e) {
      e.preventDefault();
      e.stopPropagation();
      const $this = jQuery(this),
        id = $this.attr("data-rule-id"),
        newId = parseInt(id) + 1,
        type = $this.attr("data-rule-type"),
        ruleWrap = $this.closest(".op-target-rule-selector-wrapper").find(".target_rule-builder-wrap"),
        template = wp.template("op-target-rule-" + type + "-condition"),
        fieldWrap = $this.closest(".op-target-rule-wrapper");

      ruleWrap.append(template({ id: newId, type }));

      initTargetRuleSelect2(".op-target-rule-" + type + "-on .target-rule-select2");

      $this.attr("data-rule-id", newId);

      updateCloseButton(fieldWrap);
    });

    jQuery(".op-target-rule-selector-wrapper").on("click", ".target_rule-condition-delete", function () {
      const $this = jQuery(this),
        ruleCondition = $this.closest(".op-target-rule-condition"),
        fieldWrap = $this.closest(".op-target-rule-wrapper");
      let cnt = 0;
      const dataType = fieldWrap.attr("data-type");
      if ("exclude" === dataType && fieldWrap.find(".op-target-rule-condition").length === 1) {
        fieldWrap.find(".target_rule-condition").val("");
        fieldWrap.find(".target_rule-specific-page").val("");
        fieldWrap.find(".target_rule-condition").trigger("change");
        updateExclusionButton(false, true);
      } else {
        $this.parent(".op-target-rule-condition").next(".target_rule-specific-page-wrap").remove();
        ruleCondition.remove();
      }

      fieldWrap.find(".op-target-rule-condition").each(function (i) {
        const condition = jQuery(this),
          oldRuleId = condition.attr("data-rule"),
          selectLocation = condition.find(".target_rule-condition"),
          locationName = selectLocation.attr("name");

        condition.attr("data-rule", i);

        selectLocation.attr("name", locationName.replace("[" + oldRuleId + "]", "[" + i + "]"));

        condition.removeClass("op-target-rule-" + oldRuleId).addClass("op-target-rule-" + i);

        cnt = i;
      });

      fieldWrap.find(".target_rule-add-rule-wrap a").attr("data-rule-id", cnt);

      updateCloseButton(fieldWrap);
      updateTargetRuleInput(fieldWrap);
    });

    jQuery(".op-target-rule-selector-wrapper").on("click", ".target_rule-add-exclusion-rule a", function (e) {
      e.preventDefault();
      e.stopPropagation();
      updateExclusionButton(true);
    });
  });
})(jQuery);
