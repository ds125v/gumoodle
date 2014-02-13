/**
 * A dialogue type designed to display an alert to the user.
 *
 * @module moodle-core-notification
 * @submodule moodle-core-notification-alert
 */

var ALERT_NAME = 'Moodle alert',
    ALERT;

/**
 * Extends core Dialogue to show the alert dialogue.
 *
 * @param {Object} config Object literal specifying the dialogue configuration properties.
 * @constructor
 * @class M.core.alert
 * @extends M.core.dialogue
 */
ALERT = function(config) {
    config.closeButton = false;
    ALERT.superclass.constructor.apply(this, [config]);
};
Y.extend(ALERT, M.core.dialogue, {
    closeEvents: [],
    initializer : function() {
        this.publish('complete');
        var yes = Y.Node.create('<input type="button" id="id_yuialertconfirm-' + this.get('COUNT') + '" value="'+this.get(CONFIRMYES)+'" />'),
            content = Y.Node.create('<div class="confirmation-dialogue"></div>')
                    .append(Y.Node.create('<div class="confirmation-message">'+this.get('message')+'</div>'))
                    .append(Y.Node.create('<div class="confirmation-buttons"></div>')
                            .append(yes));
        this.get(BASE).addClass('moodle-dialogue-confirm');
        this.setStdModContent(Y.WidgetStdMod.BODY, content, Y.WidgetStdMod.REPLACE);
        this.setStdModContent(Y.WidgetStdMod.HEADER,
                '<h1 id="moodle-dialogue-'+this.get('COUNT')+'-header-text">' + this.get(TITLE) + '</h1>', Y.WidgetStdMod.REPLACE);
        this.after('destroyedChange', function(){this.get(BASE).remove();}, this);
        this.closeEvents.push(
            Y.on('key', this.submit, window, 'down:13', this),
            yes.on('click', this.submit, this)
        );

        var closeButton = this.get('boundingBox').one('.closebutton');
        if (closeButton) {
            // The close button should act exactly like the 'No' button.
            this.closeEvents.push(
                closeButton.on('click', this.submit, this)
            );
        }
    },
    submit : function() {
        new Y.EventHandle(this.closeEvents).detach();
        this.fire('complete');
        this.hide();
        this.destroy();
    }
}, {
    NAME : ALERT_NAME,
    CSS_PREFIX : DIALOGUE_PREFIX,
    ATTRS : {

        /**
         * The title of the alert.
         *
         * @attribute title
         * @type String
         * @default 'Alert'
         */
        title : {
            validator : Y.Lang.isString,
            value : 'Alert'
        },

        /**
         * The message of the alert.
         *
         * @attribute message
         * @type String
         * @default 'Confirm'
         */
        message : {
            validator : Y.Lang.isString,
            value : 'Confirm'
        },

        /**
         * The button text to use to accept the alert.
         *
         * @attribute yesLabel
         * @type String
         * @default 'Ok'
         */
        yesLabel : {
            validator : Y.Lang.isString,
            setter : function(txt) {
                if (!txt) {
                    txt = 'Ok';
                }
                return txt;
            },
            value : 'Ok'
        }
    }
});

M.core.alert = ALERT;
