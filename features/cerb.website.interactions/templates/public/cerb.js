'use strict';

function CerbInteractions() {
    this.version = '{$smarty.const.APP_BUILD}';
    this.base_url = '{devblocks_url full=true}{/devblocks_url}';
    
    this.$script = document.getElementById('cerb-interactions');
    this.$head = document.getElementsByTagName('head')[0];
    this.$body = document.getElementsByTagName('body')[0];
    this.$badge = null;
    this.$popup = null;
    this.$spinner = null;
    this.focusableSelector = 'a:not([disabled]), input[type=text]:not([disabled]), textarea:not([disabled]), [tabindex]:not([disabled]):not([tabindex="-1"])';
    
    this.init();
}

CerbInteractions.prototype.getNonce = function() {
    return this.$script['nonce'] || this.$script.getAttribute('nonce');
}

CerbInteractions.prototype.init = function() {
    // Stylesheet
    var $css = document.createElement('link');
    $css.setAttribute('rel', 'stylesheet');
    $css.setAttribute('type', 'text/css');
    $css.setAttribute('async', 'true');
    $css.setAttribute('href', this.base_url + 'resource/cerb.website.interactions/css/cerb.css?v=' + this.version)
    this.$head.append($css);
    
    // Spinner
    
    var $circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    $circle.setAttribute('cx', '50');
    $circle.setAttribute('cy', '50');
    $circle.setAttribute('r', '45');
    
    this.$spinner = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    this.$spinner.setAttribute('viewBox', '0 0 100 100');
    this.$spinner.appendChild($circle);
    this.$spinner.classList.add('cerb-spinner');
    
    // Badge
    
    var badge_interaction = this.$script.getAttribute('data-cerb-badge-interaction');
    var badge_interaction_style = this.$script.getAttribute('data-cerb-badge-interaction-style');
    
    if(badge_interaction) {
        this.$badge = document.createElement('div');
        this.$badge.classList.add('cerb-interaction-badge');
        this.$badge.setAttribute('data-cerb-interaction', badge_interaction);
        
        if(badge_interaction_style)
            this.$badge.setAttribute('data-cerb-interaction-style', badge_interaction_style);
        
        var $icon = document.createElement('div');
        $icon.classList.add('cerb-interaction-badge--icon');
        this.$badge.appendChild($icon);
        
        this.$body.appendChild(this.$badge);
    }
    
    /*
    $embedder.audio = null;
    $embedder.playAudioUrl = function(url) {
        try {
            if(null == this.audio)
                this.audio = new Audio();
    
            this.audio.src = url;
            this.audio.play();
    
        } catch(e) {
            if(window.console)
                console.log(e);
        }
    }
    */

    // Hash change
    
    var hashChangeFunc = function(e) {
        e.stopPropagation();
        
        if(!window.location.hash)
            return;

        var hash = window.location.hash;

        // If we had a hash in the URL, open the interaction
        if(hash.substring(0, 2) === '#/') {
            hash = hash.substring(2);

            var params_pos = hash.indexOf('&');
            var interaction = hash.substring(0, (-1 === params_pos) ? hash.length : params_pos);
            var interaction_query = (-1 === params_pos) ? '' : hash.substring(params_pos+1);
            var interaction_style = null;
            
            inst.interactionStart(interaction, interaction_query, interaction_style);
        }
    }

    window.onhashchange = hashChangeFunc;    
    
    // Listeners
    
    var inst = this;
    var autoStarted = false;
    var $elements = document.querySelectorAll('[data-cerb-interaction]');

    var interactionClicked = function(e) {
        e.stopPropagation();

        if(!this.hasAttribute('data-cerb-interaction'))
            return;

        var interaction = this.getAttribute('data-cerb-interaction');
        var interaction_params = this.getAttribute('data-cerb-interaction-params');
        var interaction_style = this.getAttribute('data-cerb-interaction-style');

        inst.interactionStart(interaction, interaction_params, interaction_style);
    };
    
    this.forEach(
        $elements,
        function(index, el) {
            el.addEventListener('click', interactionClicked);
            
            if(!autoStarted && el.hasAttribute('data-cerb-interaction-autostart'))
                el.click();
        }
    )
    
    hashChangeFunc(this.createEvent('hashchange'));
}

CerbInteractions.prototype.createEvent = function (name, data) {
    return new CustomEvent(name, {
        detail: data
    });
}

CerbInteractions.prototype.html = function (el, html) {
    el.innerHTML = html;

    var $scripts = el.querySelectorAll('script');

    for (var i = 0; i < $scripts.length; i++) {
        var $oldScript = $scripts[i];
        var $oldNonce = $oldScript['nonce'] || $oldScript.getAttribute('nonce');
        
        if($oldNonce !== this.getNonce())
            continue;
        
        var $parent = $oldScript.parentNode;
        var $newScript = document.createElement('script');
        var scriptData = ($oldScript.text || $oldScript.textContent || $oldScript.innerHTML || "");
        $newScript.setAttribute('type', 'text/javascript');
        $newScript.setAttribute('nonce', this.getNonce());
        $newScript.appendChild(document.createTextNode(scriptData));
        $parent.insertBefore($newScript, $oldScript)
        $parent.removeChild($oldScript);
    }
}

CerbInteractions.prototype.getSpinner = function() {
    return this.$spinner.cloneNode(true);
}

CerbInteractions.prototype.disableSelection = function ($el) {
    $el.style['-webkit-touch-callout'] = 'none';
    $el.style['-webkit-user-select'] = 'none';
    $el.style['-khtml-user-select'] = 'none';
    $el.style['-moz-user-select'] = 'none';
    $el.style['-ms-user-select'] = 'none';
    $el.style['user-select'] = 'none';
}

CerbInteractions.prototype.forEach = function (array, callback, scope) {
    if(typeof array != 'object')
        return;
    
    for (var i = 0; i < array.length; i++) {
        callback.call(scope, i, array[i]);
    }
}

CerbInteractions.prototype.interactionStart = function(interaction, interaction_params, interaction_style) {
    // Check if the popup window is already open
    if(this.$popup)
        return;
    
    if (!interaction)
        return;
    
    if(this.$badge) this.$badge.style.display = 'none';

    var inst = this;
    var xhttp = new XMLHttpRequest()
    
    var formData = new FormData();
    formData.append('interaction', interaction);
    
    if(interaction_params)
        formData.append('interaction_params', interaction_params)
    
    xhttp.onreadystatechange = function () {
        if (4 === this.readyState) {
            if(200 === this.status) {
                // Open the interaction popup
                if (!inst.$popup) {
                    inst.$popup = document.createElement('div');
                    inst.$popup.className = 'cerb-interaction-popup';

                    if (interaction_style === 'full')
                        inst.$popup.className += ' cerb-interaction-popup--style-full';

                    inst.$body.append(inst.$popup);
                    inst.html(inst.$popup, this.responseText);
                    
                    inst.forEach(
                        inst.$popup.querySelectorAll('form'),
                        function(index, el) {
                            el.addEventListener('submit', function (e) {
                               return false; 
                            });
                        }
                    )

                    var $close = inst.$popup.querySelector('.cerb-interaction-popup--close');
                    
                    if($close) {
                        if(inst.$badge) {
                            $close.addEventListener('click', function (e) {
                                e.stopPropagation();
                                inst.$popup.dispatchEvent($$.createEvent('cerb-interaction-event--end'));
                            });
                        } else {
                            $close.style.display = 'none';
                        }
                    }

                    inst.$popup.addEventListener('cerb-interaction-event--submit', function (e) {
                        e.stopPropagation();
                        inst.interactionContinue(true);
                    });

                    inst.$popup.addEventListener('cerb-interaction-event--end', function (e) {
                        e.stopPropagation();
                        var eventData = {};
                        
                        if(e.detail && e.detail.eventData)
                            eventData = e.detail.eventData;
                        
                        inst.interactionEnd(eventData);
                    });

                    inst.interactionContinue(false);
                }
                    
            } else { // Not a 200 OK
                if(inst.$badge) inst.$badge.style.display = 'block';
            }
        }
    };
    
    xhttp.open('POST', '{devblocks_url full=true}c=interaction&a=start{/devblocks_url}');
    xhttp.send(formData);
}

CerbInteractions.prototype.interactionContinue = function(is_submit) {
    var $form = this.$popup.querySelector('form');
    
    if(null == $form)
        return;
    
    var $elements = $form.querySelector('.cerb-interaction-popup--form-elements');
    var xhttp = new XMLHttpRequest()
    var inst = this;
    var $spinner = this.getSpinner();
    
    $elements.appendChild($spinner);
    
    var formData = new FormData($form);
    
    if(is_submit)
        formData.append('__submit', 'continue');
    
    xhttp.onreadystatechange = function () {
        if (4 === this.readyState) {
            if(200 === this.status) {
                $spinner.remove();
                inst.html($elements, this.responseText);

                setTimeout(function () {
                    var $el = $elements.querySelector(inst.focusableSelector);
                    if ($el) $el.focus();
                }, 0);
                
            } else if (404 === this.status) {
                $spinner.remove();
                inst.interactionEnd({ 'exit': 'error' });
                
            } else {
                $spinner.remove();
                inst.interactionEnd({ 'exit': 'error' });
            }
        }
    };
    
    xhttp.open('POST', '{devblocks_url full=true}c=interaction&a=continue{/devblocks_url}');
    xhttp.send(formData);
}

CerbInteractions.prototype.interactionInvoke = function(formData, callback) {
    var xhttp = new XMLHttpRequest()

    xhttp.onreadystatechange = function () {
        if (4 === this.readyState) {
            if(200 === this.status) {
                return callback(null, this);

            } else if (404 === this.status) {
                return callback('404', this);

            } else {
                return callback('non-200', this);
            }
        }
    };

    xhttp.open('POST', '{devblocks_url full=true}c=interaction&a=invoke{/devblocks_url}');
    xhttp.send(formData);
}

CerbInteractions.prototype.interactionEnd = function(eventData) {
    if(null == eventData)
        eventData = { };
    
    this.$body.removeChild(this.$popup);
    this.$popup = null;
    if(this.$badge) this.$badge.style.display = 'block';
    
    if(eventData.hasOwnProperty('exit') && 'return' === eventData.exit) {
        if(eventData.hasOwnProperty('return') && 'object' == typeof eventData.return) {
            if(
                eventData.return.hasOwnProperty('redirect_url') 
                && 'string' == typeof eventData.return.redirect_url
                && eventData.return.redirect_url
            ) {
                document.location.href = eventData.return.redirect_url;
            }
        }
    }
}

var $$ = new CerbInteractions();