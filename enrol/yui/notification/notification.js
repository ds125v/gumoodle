YUI.add('moodle-enrol-notification', function(Y) {

var DIALOGUE_NAME = 'Moodle dialogue',
    DIALOGUE_PREFIX = 'moodle-dialogue',
    CONFIRM_NAME = 'Moodle confirmation dialogue',
    EXCEPTION_NAME = 'Moodle exception',
    AJAXEXCEPTION_NAME = 'Moodle AJAX exception',
    ALERT_NAME = 'Moodle alert',
    C = Y.Node.create,
    BASE = 'notificationBase',
    LIGHTBOX = 'lightbox',
    NODELIGHTBOX = 'nodeLightbox',
    COUNT = 0,
    CONFIRMYES = 'yesLabel',
    CONFIRMNO = 'noLabel',
    TITLE = 'title',
    QUESTION = 'question',
    CSS = {
        BASE : 'moodle-dialogue-base',
        WRAP : 'moodle-dialogue-wrap',
        HEADER : 'moodle-dialogue-hd',
        BODY : 'moodle-dialogue-bd',
        CONTENT : 'moodle-dialogue-content',
        FOOTER : 'moodle-dialogue-fd',
        HIDDEN : 'hidden',
        LIGHTBOX : 'moodle-dialogue-lightbox'
    };

var DIALOGUE = function(config) {
    COUNT++;
    var id = 'moodle-dialogue-'+COUNT;
    config.notificationBase =
        C('<div class="'+CSS.BASE+'">')
            .append(C('<div class="'+CSS.LIGHTBOX+' '+CSS.HIDDEN+'"></div>'))
            .append(C('<div id="'+id+'" class="'+CSS.WRAP+'"></div>')
                .append(C('<div class="'+CSS.HEADER+' yui3-widget-hd"></div>'))
                .append(C('<div class="'+CSS.BODY+' yui3-widget-bd"></div>'))
                .append(C('<div class="'+CSS.CONTENT+' yui3-widget-ft"></div>')));
    Y.one(document.body).append(config.notificationBase);
    config.srcNode =    '#'+id;
    config.width =      config.width || '400px';
    config.visible =    config.visible || false;
    config.center =     config.centered || true;
    config.centered =   false;
    DIALOGUE.superclass.constructor.apply(this, [config]);
};
Y.extend(DIALOGUE, Y.Overlay, {
    initializer : function(config) {
        this.set(NODELIGHTBOX, this.get(BASE).one('.'+CSS.LIGHTBOX).setStyle('opacity', 0.5));
        this.after('visibleChange', this.visibilityChanged, this);
        this.after('headerContentChange', function(e){
            var h = (this.get('closeButton'))?this.get(BASE).one('.'+CSS.HEADER):false;
            if (h && !h.one('.closebutton')) {
                var c = C('<div class="closebutton"></div>');
                c.on('click', this.hide, this);
                h.append(c);
            }
        }, this);
        this.render();
        this.show();
    },
    visibilityChanged : function(e) {
        switch (e.attrName) {
            case 'visible':
                if (this.get(LIGHTBOX)) {
                    var l = this.get(NODELIGHTBOX);
                    if (!e.prevVal && e.newVal) {
                        l.setStyle('height',l.get('docHeight')+'px').removeClass(CSS.HIDDEN);
                    } else if (e.prevVal && !e.newVal) {
                        l.addClass(CSS.HIDDEN);
                    }
                }
                if (this.get('center') && !e.prevVal && e.newVal) {
                    this.centerDialogue();
                }
                if (this.get('draggable')) {
                    var titlebar = '#' + this.get('id') + ' .' + CSS.HEADER;
                    this.plug(Y.Plugin.Drag, {handles : [titlebar]});
                    this.dd.addInvalid('div.closebutton');
                    Y.one(titlebar).setStyle('cursor', 'move');
                }
                break;
        }
    },
    centerDialogue : function() {
        var bb = this.get('boundingBox'), hidden = bb.hasClass(DIALOGUE_PREFIX+'-hidden');
        if (hidden) {
            bb.setStyle('top', '-1000px').removeClass(DIALOGUE_PREFIX+'-hidden');
        }
        var x = Math.max(Math.round((bb.get('winWidth') - bb.get('offsetWidth'))/2), 15);
        var y = Math.max(Math.round((bb.get('winHeight') - bb.get('offsetHeight'))/2), 15) + Y.one(window).get('scrollTop');

        if (hidden) {
            bb.addClass(DIALOGUE_PREFIX+'-hidden');
        }
        bb.setStyle('left', x).setStyle('top', y);
    }
}, {
    NAME : DIALOGUE_NAME,
    CSS_PREFIX : DIALOGUE_PREFIX,
    ATTRS : {
        notificationBase : {

        },
        nodeLightbox : {
            value : null
        },
        lightbox : {
            validator : Y.Lang.isBoolean,
            value : true
        },
        closeButton : {
            validator : Y.Lang.isBoolean,
            value : true
        },
        center : {
            validator : Y.Lang.isBoolean,
            value : true
        },
        draggable : {
            validator : Y.Lang.isBoolean,
            value : false
        }
    }
});

var ALERT = function(config) {
    config.closeButton = false;
    ALERT.superclass.constructor.apply(this, [config]);
};
Y.extend(ALERT, DIALOGUE, {
    _enterKeypress : null,
    initializer : function(config) {
        this.publish('complete');
        var yes = C('<input type="button" value="'+this.get(CONFIRMYES)+'" />'),
            content = C('<div class="confirmation-dialogue"></div>')
                    .append(C('<div class="confirmation-message">'+this.get('message')+'</div>'))
                    .append(C('<div class="confirmation-buttons"></div>')
                            .append(yes));
        this.get(BASE).addClass('moodle-dialogue-confirm');
        this.setStdModContent(Y.WidgetStdMod.BODY, content, Y.WidgetStdMod.REPLACE);
        this.setStdModContent(Y.WidgetStdMod.HEADER, this.get(TITLE), Y.WidgetStdMod.REPLACE);
        this.after('destroyedChange', function(){this.get(BASE).remove();}, this);
        this._enterKeypress = Y.on('key', this.submit, window, 'down:13', this);
        yes.on('click', this.submit, this);
    },
    submit : function(e, outcome) {
        this._enterKeypress.detach();
        this.fire('complete');
        this.hide();
        this.destroy();
    }
}, {
    NAME : ALERT_NAME,
    CSS_PREFIX : DIALOGUE_PREFIX,
    ATTRS : {
        title : {
            validator : Y.Lang.isString,
            value : 'Alert'
        },
        message : {
            validator : Y.Lang.isString,
            value : 'Confirm'
        },
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

var CONFIRM = function(config) {
    CONFIRM.superclass.constructor.apply(this, [config]);
};
Y.extend(CONFIRM, DIALOGUE, {
    _enterKeypress : null,
    _escKeypress : null,
    initializer : function(config) {
        this.publish('complete');
        this.publish('complete-yes');
        this.publish('complete-no');
        var yes = C('<input type="button" value="'+this.get(CONFIRMYES)+'" />'),
            no = C('<input type="button" value="'+this.get(CONFIRMNO)+'" />'),
            content = C('<div class="confirmation-dialogue"></div>')
                        .append(C('<div class="confirmation-message">'+this.get(QUESTION)+'</div>'))
                        .append(C('<div class="confirmation-buttons"></div>')
                            .append(yes)
                            .append(no));
        this.get(BASE).addClass('moodle-dialogue-confirm');
        this.setStdModContent(Y.WidgetStdMod.BODY, content, Y.WidgetStdMod.REPLACE);
        this.setStdModContent(Y.WidgetStdMod.HEADER, this.get(TITLE), Y.WidgetStdMod.REPLACE);
        this.after('destroyedChange', function(){this.get(BASE).remove();}, this);
        this._enterKeypress = Y.on('key', this.submit, window, 'down:13', this, true);
        this._escKeypress = Y.on('key', this.submit, window, 'down:27', this, false);
        yes.on('click', this.submit, this, true);
        no.on('click', this.submit, this, false);
    },
    submit : function(e, outcome) {
        this._enterKeypress.detach();
        this._escKeypress.detach();
        this.fire('complete', outcome);
        if (outcome) {
            this.fire('complete-yes');
        } else {
            this.fire('complete-no');
        }
        this.hide();
        this.destroy();
    }
}, {
    NAME : CONFIRM_NAME,
    CSS_PREFIX : DIALOGUE_PREFIX,
    ATTRS : {
        yesLabel : {
            validator : Y.Lang.isString,
            value : 'Yes'
        },
        noLabel : {
            validator : Y.Lang.isString,
            value : 'No'
        },
        title : {
            validator : Y.Lang.isString,
            value : 'Confirm'
        },
        question : {
            validator : Y.Lang.isString,
            value : 'Are you sure?'
        }
    }
});
Y.augment(CONFIRM, Y.EventTarget);

var EXCEPTION = function(config) {
    config.width = config.width || (M.cfg.developerdebug)?Math.floor(Y.one(document.body).get('winWidth')/3)+'px':null;
    config.closeButton = true;
    EXCEPTION.superclass.constructor.apply(this, [config]);
};
Y.extend(EXCEPTION, DIALOGUE, {
    _hideTimeout : null,
    _keypress : null,
    initializer : function(config) {
        this.get(BASE).addClass('moodle-dialogue-exception');
        this.setStdModContent(Y.WidgetStdMod.HEADER, config.name, Y.WidgetStdMod.REPLACE);
        var content = C('<div class="moodle-exception"></div>')
                    .append(C('<div class="moodle-exception-message">'+this.get('message')+'</div>'))
                    .append(C('<div class="moodle-exception-param hidden param-filename"><label>File:</label> '+this.get('fileName')+'</div>'))
                    .append(C('<div class="moodle-exception-param hidden param-linenumber"><label>Line:</label> '+this.get('lineNumber')+'</div>'))
                    .append(C('<div class="moodle-exception-param hidden param-stacktrace"><label>Stack trace:</label> <pre>'+this.get('stack')+'</pre></div>'));
        if (M.cfg.developerdebug) {
            content.all('.moodle-exception-param').removeClass('hidden');
        }
        this.setStdModContent(Y.WidgetStdMod.BODY, content, Y.WidgetStdMod.REPLACE);

        var self = this;
        var delay = this.get('hideTimeoutDelay');
        if (delay) {
            this._hideTimeout = setTimeout(function(){self.hide();}, delay);
        }
        this.after('visibleChange', this.visibilityChanged, this);
        this.after('destroyedChange', function(){this.get(BASE).remove();}, this);
        this._keypress = Y.on('key', this.hide, window, 'down:13,27', this);
        this.centerDialogue();
    },
    visibilityChanged : function(e) {
        if (e.attrName == 'visible' && e.prevVal && !e.newVal) {
            if (this._keypress) this._keypress.detach();
            var self = this;
            setTimeout(function(){self.destroy();}, 1000);
        }
    }
}, {
    NAME : EXCEPTION_NAME,
    CSS_PREFIX : DIALOGUE_PREFIX,
    ATTRS : {
        message : {
            value : ''
        },
        name : {
            value : ''
        },
        fileName : {
            value : ''
        },
        lineNumber : {
            value : ''
        },
        stack : {
            setter : function(str) {
                var lines = str.split("\n");
                var pattern = new RegExp('^(.+)@('+M.cfg.wwwroot+')?(.{0,75}).*:(\\d+)$');
                for (var i in lines) {
                    lines[i] = lines[i].replace(pattern, "<div class='stacktrace-line'>ln: $4</div><div class='stacktrace-file'>$3</div><div class='stacktrace-call'>$1</div>");
                }
                return lines.join('');
            },
            value : ''
        },
        hideTimeoutDelay : {
            validator : Y.Lang.isNumber,
            value : null
        }
    }
});

var AJAXEXCEPTION = function(config) {
    config.name = config.name || 'Error';
    config.closeButton = true;
    AJAXEXCEPTION.superclass.constructor.apply(this, [config]);
};
Y.extend(AJAXEXCEPTION, DIALOGUE, {
    _keypress : null,
    initializer : function(config) {
        this.get(BASE).addClass('moodle-dialogue-exception');
        this.setStdModContent(Y.WidgetStdMod.HEADER, config.name, Y.WidgetStdMod.REPLACE);
        var content = C('<div class="moodle-ajaxexception"></div>')
                    .append(C('<div class="moodle-exception-message">'+this.get('error')+'</div>'))
                    .append(C('<div class="moodle-exception-param hidden param-debuginfo"><label>URL:</label> '+this.get('reproductionlink')+'</div>'))
                    .append(C('<div class="moodle-exception-param hidden param-debuginfo"><label>Debug info:</label> '+this.get('debuginfo')+'</div>'))
                    .append(C('<div class="moodle-exception-param hidden param-stacktrace"><label>Stack trace:</label> <pre>'+this.get('stacktrace')+'</pre></div>'));
        if (M.cfg.developerdebug) {
            content.all('.moodle-exception-param').removeClass('hidden');
        }
        this.setStdModContent(Y.WidgetStdMod.BODY, content, Y.WidgetStdMod.REPLACE);

        var self = this;
        var delay = this.get('hideTimeoutDelay');
        if (delay) {
            this._hideTimeout = setTimeout(function(){self.hide();}, delay);
        }
        this.after('visibleChange', this.visibilityChanged, this);
        this._keypress = Y.on('key', this.hide, window, 'down:13, 27', this);
        this.centerDialogue();
    },
    visibilityChanged : function(e) {
        if (e.attrName == 'visible' && e.prevVal && !e.newVal) {
            var self = this;
            this._keypress.detach();
            setTimeout(function(){self.destroy();}, 1000);
        }
    }
}, {
    NAME : AJAXEXCEPTION_NAME,
    CSS_PREFIX : DIALOGUE_PREFIX,
    ATTRS : {
        error : {
            validator : Y.Lang.isString,
            value : 'Unknown error'
        },
        debuginfo : {
            value : null
        },
        stacktrace : {
            value : null
        },
        reproductionlink : {
            setter : function(link) {
                if (link !== null) {
                    link = '<a href="'+link+'">'+link.replace(M.cfg.wwwroot, '')+'</a>';
                }
                return link;
            },
            value : null
        },
        hideTimeoutDelay : {
            validator : Y.Lang.isNumber,
            value : null
        }
    }
});

M.core = M.core || {};
M.core.dialogue = DIALOGUE;
M.core.alert = ALERT;
M.core.confirm = CONFIRM;
M.core.exception = EXCEPTION;
M.core.ajaxException = AJAXEXCEPTION;

}, '@VERSION@', {requires:['base','node','overlay','event-key', 'moodle-enrol-notification-skin', 'dd-plugin']});
