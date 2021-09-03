define(['jquery', 'fab/fabrik'], function (jQuery, Fabrik) {
    'use strict';
    var FbListAdmin = new Class({

        Implements: [Events],

        initialize: function (options) {
            this.options = jQuery.extend(this.options, options);

            if (this.options.view === 'form') {
                this.addWatchButton();
            }
        },
        addWatchButton: function () {
            var button = document.getElementById('list_selected');
            var fields = document.getElementById('list_admin_fields');

            button.onclick = () => {
                var listSelected = document.getElementById('list_admin_select_list');
                jQuery.ajax ({
                    url: Fabrik.liveSite + 'index.php',
                    method: "POST",
                    data: {
                        'option': 'com_fabrik',
                        'format': 'raw',
                        'task': 'plugin.pluginAjax',
                        'plugin': 'list_admin',
                        'method': 'createListFieldsHtml',
                        'g': 'element',
                        'element_id': this.options.element_id,
                        'list_selected': listSelected.value
                    }
                }).done ((data) => {
                    var html = document.createElement('div');
                    html.innerHTML = JSON.parse(data);

                    while (fields.firstChild) {
                        fields.removeChild(fields.lastChild);
                    }

                    fields.appendChild(html.firstChild);
                });
            };
        }
    });

    return FbListAdmin;
});
