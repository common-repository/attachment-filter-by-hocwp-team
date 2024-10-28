window.wp = window.wp || {};
window.wpaf = window.wpaf || {};

(function ($) {
    if ("object" !== typeof wp) {
        return;
    }

    if ("function" !== typeof wp.media) {
        return;
    }

    var taxonomies = wpaf.taxonomies;

    if ("object" == typeof taxonomies) {
        for (var key in taxonomies) {
            if (taxonomies.hasOwnProperty(key)) {
                var data = taxonomies[key];

                (function (data) {
                    var terms = data.terms,
                        taxonomy = data.taxonomy.name,
                        fieldId = "media-attachment-taxonomy-" + taxonomy + "-filter";

                    var MediaLibraryTaxonomyFilter = wp.media.view.AttachmentFilters.extend({
                        id: fieldId,
                        createFilters: function () {
                            var filters = {};

                            $.each(terms, function (index, value) {
                                var props = {};

                                props[taxonomy] = value.slug;

                                filters[index] = {
                                    text: value.name,
                                    props: props
                                };
                            });

                            var props = {};

                            props[taxonomy] = "";

                            filters.all = {
                                text: data.option_all,
                                props: props,
                                priority: 10
                            };

                            this.filters = filters;
                        }
                    });

                    var AttachmentsBrowser = wp.media.view.AttachmentsBrowser;

                    wp.media.view.AttachmentsBrowser = wp.media.view.AttachmentsBrowser.extend({
                        createToolbar: function () {
                            AttachmentsBrowser.prototype.createToolbar.call(this);

                            this.toolbar.set(taxonomy + "Label", new wp.media.view.Label({
                                value: data.label,
                                attributes: {
                                    "for": fieldId
                                },
                                priority: -75
                            }).render());

                            this.toolbar.set(taxonomy + "MediaLibraryTaxonomyFilter", new MediaLibraryTaxonomyFilter({
                                controller: this.controller,
                                model: this.collection.props,
                                priority: -75
                            }).render());
                        }
                    });
                })(data);
            }
        }
    }
})(jQuery);

(function ($) {
    var body = $("body"),
        selectCategory = $(".actions select.taxonomy-filter");

    if (selectCategory.length) {
        var submitTop = body.find(".tablenav.top .actions input[type='submit']"),
            submitBottom = body.find(".tablenav.bottom .actions input[type='submit']"),
            i,
            selectClone;

        for (i = 0; i < selectCategory.length; i++) {
            selectClone = $(selectCategory[i]).clone();
            selectClone.attr("id", "");
            selectClone.addClass("select-category");
            selectClone.hide();
            var name = selectClone.attr("name");

            if (submitTop.length) {
                selectClone.attr("name", name + "_top");
                submitTop.before(selectClone);
                submitTop.addClass("submit-top");
            }

            if (submitBottom.length) {
                selectClone = selectClone.clone();
                selectClone.attr("name", name + "_bottom");
                submitBottom.before(selectClone);
                submitBottom.addClass("submit-bottom");
            }
        }

        body.on("change", ".actions select[name='action'],.actions select[name='action2']", function () {
            var element = $(this),
                action = element.val(),
                container = element.parent();
            if ("change_category" === action) {
                container.children(".select-category").fadeIn();
            } else {
                container.children(".select-category").fadeOut();
            }
        });

        $("#posts-filter").on("submit", function (e) {
            var element = $(this),
                selectAction = element.find(".actions select[name='action']"),
                selectCategory = selectAction.next(),
                selectAction2 = element.find(".actions select[name='action2']"),
                selectCategory2 = selectAction2.next();
            if (selectAction.length && selectCategory.length && "change_category" === selectAction.val() && "" === selectCategory.val()) {
                e.preventDefault();
                selectCategory.focus();
            } else if (selectAction2.length && selectCategory2.length && "change_category" === selectAction2.val() && "" === selectCategory2.val()) {
                e.preventDefault();
                selectCategory2.focus();
            }
        });
    }
})(jQuery);

jQuery(document).ready(function ($) {
    function search(nameKey, myArray) {
        for (var i = 0; i < myArray.length; i++) {
            if (myArray[i].name === nameKey) {
                return myArray[i];
            }
        }
    }

    (function () {
        $(".wrap .filter-form").on("submit", function (e) {
            var element = $(this),
                data = element.serializeArray(),
                action = search("action", data),
                action2 = search("action2", data);

            if ("delete" === action.value || "delete" === action2.value) {
                if (!confirm(wpaf.confirmDelete)) {
                    e.preventDefault();
                }
            }
        });
    })();
});