// @koala-prepend "visualcaptcha.vanilla.js"
// @koala-prepend "clipboard.min.js"

/*
    Copyright (C) 2018  GoodOldDownloads

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

document.body.classList.remove('no-js');

var http = function() {
    this.toParam = function(obj){
        var query = [];
        for (var key in obj) {
            query.push(encodeURIComponent(key) + '=' + encodeURIComponent(obj[key]));
        }
        return query.join('&');
    };
    this.fromParam = function(str){
        return JSON.parse('{"' + decodeURI(str).replace(/"/g, '\\"').replace(/&/g, '","').replace(/=/g,'":"') + '"}');
    };
    this.get = function(settings, call) {
        var param = this.toParam(settings.data);
        var xmlHttp = new XMLHttpRequest();
        xmlHttp.onreadystatechange = function() { 
            if (xmlHttp.readyState == 4 && xmlHttp.status == 200){
                call(xmlHttp.responseText);
            }
        };

        xmlHttp.open('GET', settings.url+'?'+param, true);
        if (typeof(settings.headers) !== 'undefined') {
            for (var key in settings.headers) {
                // check if the property/key is defined in the object itself, not in parent
                if (settings.headers.hasOwnProperty(key)) {
                    xmlHttp.setRequestHeader(key, settings.headers[key]);
                }
            }
        }
        xmlHttp.send(null);
        return xmlHttp;
    };
    this.post = function(settings, call) {
        if (typeof(call) === 'undefined') {
            call = function(){};
        }
        var formdata = true;
        try {
           settings.data.entries(); // test if formdata
        } catch (e) {
            formdata = false;
        }
        var xmlHttp = new XMLHttpRequest();
        xmlHttp.onreadystatechange = function() { 
            if (xmlHttp.readyState == 4 && xmlHttp.status == 200){
                call(xmlHttp.responseText);
            }
        };
        xmlHttp.open('POST', settings.url, true);
        if (typeof(settings.headers) !== 'undefined') {
            for (var key in settings.headers) {
                // check if the property/key is defined in the object itself, not in parent
                if (settings.headers.hasOwnProperty(key)) {
                    xmlHttp.setRequestHeader(key, settings.headers[key]);
                }
            }
        }
        if(formdata){
            xmlHttp.send(settings.data);
        } else {
            var param = this.toParam(settings.data);
            xmlHttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xmlHttp.send(param);
        }
        return xmlHttp;
    };
};
var http = new http();

new ClipboardJS('.clip', {
  text: function(trigger) {
    var anchors = trigger.nextElementSibling.nextElementSibling.querySelectorAll('.link > a');
    var links = [];
    for (var i = 0; i < anchors.length; i++) {
      var node = anchors[i];
      links.push(node.href);
    }
    return links.join('\n');
  }
});

// "Open all links" button
document.addEventListener('click', function(evt) {
    if (evt.target && evt.target.classList.contains('open-all') || evt.target.closest('.open-all')) {
        var target = evt.target;
        if (evt.target.closest('.open-all')) {
            target = evt.target.closest('.open-all');
        }
        var urls = [];
        var anchors = target.nextElementSibling.nextElementSibling.nextElementSibling.querySelectorAll('.link > a');
        for (var i = 0; i < anchors.length; i++) {
            var node = anchors[i];
            urls.push(node.href);
        }
        var delay = 0;
        for (var i = 0; i < urls.length; i++) {
            if (navigator.userAgent.toLowerCase().indexOf('firefox') > -1){
                (function(index) {
                    setTimeout(function(){
                        var a = document.createElement('a');
                        a.download = '';
                        a.href = urls[index];
                        a.target = '_blank';
                        a.dispatchEvent(new MouseEvent('click'));
                    }, 100 * ++delay);
                })(i);
            } else {
                (function(index) {
                    setTimeout(function(){
                        window.open(urls[index], '_blank');
                    }, 1000);
                })(i);
            }
        }
    }
}, false);

// Make nicer URL for people who have JS enabled
document.querySelector('#search-bar').addEventListener('submit', function(evt){
  evt.preventDefault();
  var term = this.querySelector('input[name="t"]').value;
  var sconsole = this.querySelector('[name="c"]').value;
  var region = this.querySelector('[name="r"]').value;
  if (term === '') {
    term = 'all';
  }
  if (region !== 'any') {
    region = '/'+region;
  } else {
    region = '';
  }

  window.location.href = '/search/'+sconsole+'/'+encodeURIComponent(term)+region;
});

/////
// Captcha
/////
var captcha;
document.addEventListener('click', function(evt) {
    if (evt.target && evt.target.matches('.__vote-modal-trigger')) {
        evt.stopPropagation();
        evt.preventDefault();

        var id =  parseInt(evt.target.dataset.id);
        document.getElementById('vote-captcha-wrap').classList.remove('hidden');
        captcha = visualCaptcha('vote-captcha', {
            captcha: {
                numberOfImages: 8,
                url: window.location.origin+'/api/captcha',
                randomParam: 'what-are-you-looking-at',
                routes: {
                    start: '/begin',
                    image: '/img',
                },
                callbacks: {
                    loaded: function(captcha){
                        captcha.releaseId = id;
                        captcha.voteTrigger = evt.target;

                        // Stop # when clicking anchors
                        var anchorOptions = document.getElementById('vote-captcha').getElementsByClassName('img');
                        var anchorList = Array.prototype.slice.call(anchorOptions);
                        anchorList.forEach(function(anchor){
                            anchor.addEventListener('click', function(evt){
                                evt.preventDefault();
                                //voteBtn.classList.remove('hidden');
                            }, false);
                        });
                    }
                }
            }
        });
    }

    if (evt.target && evt.target.matches('#vote-btn')) {
      evt.preventDefault();
      evt.stopPropagation();
      var captchaData = captcha.getCaptchaData();
      if (captchaData.valid) {
          var capName = captcha.imageFieldName();
          var capValue = captchaData.value;
          var postData = {rom_id: captcha.releaseId};
          postData[capName] = capValue;
          var captchaMsg = document.getElementById('vote-captcha-message');
          var captchaMsgSuccess = document.getElementById('vote-captcha-success');
          http.post({
              url: '/api/captcha/vote',
              data: postData
          }, function(res){
              var ret = JSON.parse(res);
              if (ret.SUCCESS) {
                  captcha.voteTrigger.classList.add('hidden');
                  document.querySelector('.__vote-modal-trigger').classList.add('hidden');
                  document.querySelector('#vote-captcha').classList.add('hidden');
                  document.querySelector('#vote-btn').classList.add('hidden');
                  captchaMsgSuccess.classList.remove('hidden');
                  captchaMsg.classList.add('hidden');
              } else {
                  captcha.refresh();
                  captchaMsgSuccess.classList.add('hidden');
                  captchaMsg.classList.remove('hidden');
                  captchaMsg.innerText = ret.MSG;
              }
          });
      }
    }
}, false);