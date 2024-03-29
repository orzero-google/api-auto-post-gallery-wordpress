﻿var igg = 0,
gotgg = -1,
lengg = document.getElementsByTagName('script').length;
while (igg <= lengg && gotgg == -1) {
	var js_url = document.getElementsByTagName('script')[igg].src,
	gotgg = js_url.indexOf('ajaxupload.js');
	igg++
}
var wp_url = js_url.substr(0, js_url.indexOf('wp-content')) + '/wp-admin/admin-ajax.php';
(function() {
	function addEvent(el, type, fn) {
		if (el.addEventListener) {
			el.addEventListener(type, fn, false)
		} else if (el.attachEvent) {
			el.attachEvent('on' + type,
			function() {
				fn.call(el)
			})
		} else {
			throw new Error('not supported or DOM not loaded')
		}
	}
	function addResizeEvent(fn) {
		var timeout;
		addEvent(window, 'resize',
		function() {
			if (timeout) {
				clearTimeout(timeout)
			}
			timeout = setTimeout(fn, 100)
		})
	}
	if (document.documentElement.getBoundingClientRect) {
		var getOffset = function(el) {
			var box = el.getBoundingClientRect();
			var doc = el.ownerDocument;
			var body = doc.body;
			var docElem = doc.documentElement;
			var clientTop = docElem.clientTop || body.clientTop || 0;
			var clientLeft = docElem.clientLeft || body.clientLeft || 0;
			var zoom = 1;
			if (body.getBoundingClientRect) {
				var bound = body.getBoundingClientRect();
				zoom = (bound.right - bound.left) / body.clientWidth
			}
			if (zoom > 1) {
				clientTop = 0;
				clientLeft = 0
			}
			var top = box.top / zoom + (window.pageYOffset || docElem && docElem.scrollTop / zoom || body.scrollTop / zoom) - clientTop,
			left = box.left / zoom + (window.pageXOffset || docElem && docElem.scrollLeft / zoom || body.scrollLeft / zoom) - clientLeft;
			return {
				top: top,
				left: left
			}
		}
	} else {
		var getOffset = function(el) {
			var top = 0,
			left = 0;
			do {
				top += el.offsetTop || 0;
				left += el.offsetLeft || 0;
				el = el.offsetParent
			} while ( el );
			return {
				left: left,
				top: top
			}
		}
	}
	function getBox(el) {
		var left, right, top, bottom;
		var offset = getOffset(el);
		left = offset.left;
		top = offset.top;
		right = left + el.offsetWidth;
		bottom = top + el.offsetHeight;
		return {
			left: left,
			right: right,
			top: top,
			bottom: bottom
		}
	}
	function addStyles(el, styles) {
		for (var name in styles) {
			if (styles.hasOwnProperty(name)) {
				el.style[name] = styles[name]
			}
		}
	}
	function copyLayout(from, to) {
		var box = getBox(from);
		addStyles(to, {
			position: 'absolute',
			left: box.left + 'px',
			top: box.top + 'px',
			width: from.offsetWidth + 'px',
			height: from.offsetHeight + 'px'
		})
	}
	var toElement = (function() {
		var div = document.createElement('div');
		return function(html) {
			div.innerHTML = html;
			var el = div.firstChild;
			return div.removeChild(el)
		}
	})();
	var getUID = (function() {
		var id = 0;
		return function() {
			return 'ValumsAjaxUpload' + id++
		}
	})();
	function fileFromPath(file) {
		return file.replace(/.*(\/|\\)/, "")
	}
	function getExt(file) {
		return ( - 1 !== file.indexOf('.')) ? file.replace(/.*[.]/, '') : ''
	}
	function hasClass(el, name) {
		var re = new RegExp('\\b' + name + '\\b');
		return re.test(el.className)
	}
	function addClass(el, name) {
		if (!hasClass(el, name)) {
			el.className += ' ' + name
		}
	}
	function removeClass(el, name) {
		var re = new RegExp('\\b' + name + '\\b');
		el.className = el.className.replace(re, '')
	}
	function removeNode(el) {
		el.parentNode.removeChild(el)
	}
	window.AjaxUpload = function(button, options) {
		this._settings = {
			action: 'upload.php',
			name: 'userfile',
			multiple: false,
			data: {},
			autoSubmit: true,
			responseType: false,
			hoverClass: 'hover',
			focusClass: 'focus',
			disabledClass: 'disabled',
			onChange: function(file, extension) {},
			onSubmit: function(file, extension) {},
			onComplete: function(file, response) {}
		};
		for (var i in options) {
			if (options.hasOwnProperty(i)) {
				this._settings[i] = options[i]
			}
		}
		if (button.jquery) {
			button = button[0]
		} else if (typeof button == "string") {
			if (/^#.*/.test(button)) {
				button = button.slice(1)
			}
			button = document.getElementById(button)
		}
		if (!button || button.nodeType !== 1) {
			throw new Error("Please make sure that you're passing a valid element")
		}
		if (button.nodeName.toUpperCase() == 'A') {
			addEvent(button, 'click',
			function(e) {
				if (e && e.preventDefault) {
					e.preventDefault()
				} else if (window.event) {
					window.event.returnValue = false
				}
			})
		}
		this._button = button;
		this._input = null;
		this._disabled = false;
		this.enable();
		this._rerouteClicks()
	};
	AjaxUpload.prototype = {
		setData: function(data) {
			this._settings.data = data
		},
		disable: function() {
			addClass(this._button, this._settings.disabledClass);
			this._disabled = true;
			var nodeName = this._button.nodeName.toUpperCase();
			if (nodeName == 'INPUT' || nodeName == 'BUTTON') {
				this._button.setAttribute('disabled', 'disabled')
			}
			if (this._input) {
				if (this._input.parentNode) {
					this._input.parentNode.style.visibility = 'hidden'
				}
			}
		},
		enable: function() {
			removeClass(this._button, this._settings.disabledClass);
			this._button.removeAttribute('disabled');
			this._disabled = false
		},
		_createInput: function() {
			var self = this;
			var input = document.createElement("input");
			input.setAttribute('type', 'file');
			input.setAttribute('name', this._settings.name);
			if (this._settings.multiple) input.setAttribute('multiple', 'multiple');
			addStyles(input, {
				'position': 'absolute',
				'right': 0,
				'margin': 0,
				'padding': 0,
				'fontSize': '480px',
				'fontFamily': 'sans-serif',
				'cursor': 'pointer'
			});
			var div = document.createElement("div");
			addStyles(div, {
				'display': 'block',
				'position': 'absolute',
				'overflow': 'hidden',
				'margin': 0,
				'padding': 0,
				'opacity': 0,
				'direction': 'ltr',
				'zIndex': 2147483583
			});
			if (div.style.opacity !== "0") {
				if (typeof(div.filters) == 'undefined') {
					throw new Error('Opacity not supported by the browser')
				}
				div.style.filter = "alpha(opacity=0)"
			}
			addEvent(input, 'change',
			function() {
				if (!input || input.value === '') {
					return
				}
				var file = fileFromPath(input.value);
				if (false === self._settings.onChange.call(self, file, getExt(file))) {
					self._clearInput();
					return
				}
				if (self._settings.autoSubmit) {
					self.submit()
				}
			});
			addEvent(input, 'mouseover',
			function() {
				addClass(self._button, self._settings.hoverClass)
			});
			addEvent(input, 'mouseout',
			function() {
				removeClass(self._button, self._settings.hoverClass);
				removeClass(self._button, self._settings.focusClass);
				if (input.parentNode) {
					input.parentNode.style.visibility = 'hidden'
				}
			});
			addEvent(input, 'focus',
			function() {
				addClass(self._button, self._settings.focusClass)
			});
			addEvent(input, 'blur',
			function() {
				removeClass(self._button, self._settings.focusClass)
			});
			div.appendChild(input);
			document.body.appendChild(div);
			this._input = input
		},
		_clearInput: function() {
			if (!this._input) {
				return
			}
			removeNode(this._input.parentNode);
			this._input = null;
			this._createInput();
			removeClass(this._button, this._settings.hoverClass);
			removeClass(this._button, this._settings.focusClass)
		},
		_rerouteClicks: function() {
			var self = this;
			addEvent(self._button, 'mouseover',
			function() {
				if (self._disabled) {
					return
				}
				if (!self._input) {
					self._createInput()
				}
				var div = self._input.parentNode;
				copyLayout(self._button, div);
				div.style.visibility = 'visible'
			})
		},
		_createIframe: function() {
			var id = getUID();
			var iframe = toElement('<iframe src="javascript:false;" name="' + id + '" />');
			iframe.setAttribute('id', id);
			iframe.style.display = 'none';
			document.body.appendChild(iframe);
			return iframe
		},
		_createForm: function(iframe) {
			var settings = this._settings;
			var form = toElement('<form method="post" enctype="multipart/form-data"></form>');
			form.setAttribute('action', settings.action);
			form.setAttribute('target', iframe.name);
			form.style.display = 'none';
			document.body.appendChild(form);
			for (var prop in settings.data) {
				if (settings.data.hasOwnProperty(prop)) {
					var el = document.createElement("input");
					el.setAttribute('type', 'hidden');
					el.setAttribute('name', prop);
					el.setAttribute('value', settings.data[prop]);
					form.appendChild(el)
				}
			}
			return form
		},
		_getResponse: function(iframe, file) {
			var toDeleteFlag = false,
			self = this,
			settings = this._settings;
			addEvent(iframe, 'load',
			function() {
				if (iframe.src == "javascript:'%3Chtml%3E%3C/html%3E';" || iframe.src == "javascript:'<html></html>';") {
					if (toDeleteFlag) {
						setTimeout(function() {
							removeNode(iframe)
						},
						0)
					}
					return
				}
				var doc = iframe.contentDocument ? iframe.contentDocument: window.frames[iframe.id].document;
				if (doc.readyState && doc.readyState != 'complete') {
					return
				}
				if (doc.body && doc.body.innerHTML == "false") {
					return
				}
				var response;
				if (doc.XMLDocument) {
					response = doc.XMLDocument
				} else if (doc.body) {
					response = doc.body.innerHTML;
					if (settings.responseType && settings.responseType.toLowerCase() == 'json') {
						if (doc.body.firstChild && doc.body.firstChild.nodeName.toUpperCase() == 'PRE') {
							doc.normalize();
							response = doc.body.firstChild.firstChild.nodeValue
						}
						if (response) {
							response = eval("(" + response + ")")
						} else {
							response = {}
						}
					}
				} else {
					response = doc
				}
				settings.onComplete.call(self, file, response);
				toDeleteFlag = true;
				iframe.src = "javascript:'<html></html>';"
			})
		},
		submit: function() {
			var self = this,
			settings = this._settings;
			if (!this._input || this._input.value === '') {
				return
			}
			var file = fileFromPath(this._input.value);
			if (false === settings.onSubmit.call(this, file, getExt(file))) {
				this._clearInput();
				return
			}
			var iframe = this._createIframe();
			var form = this._createForm(iframe);
			removeNode(this._input.parentNode);
			removeClass(self._button, self._settings.hoverClass);
			removeClass(self._button, self._settings.focusClass);
			form.appendChild(this._input);
			form.submit();
			removeNode(form);
			form = null;
			removeNode(this._input);
			this._input = null;
			this._getResponse(iframe, file);
			this._createInput()
		}
	}
})();
jQuery(document).ready(function($) {
	var hideImages = function() {
		if (!$("#upload-images").is(".hidden")) {
			$("#upload-button").removeClass("active");
			$("#upload-images").addClass("hidden")
		}
	};
	$("#upload-button").click(function() {
		$(this).toggleClass("active");
		if ($("#upload-images").hasClass("hidden")) {
			$("#upload-images").removeClass("hidden")
		} else {
			$("#upload-images").addClass("hidden")
		};
		return false
	});
	$("body").click(function() {
		hideImages()
	});
	$('#upload-images,#upload-images input').click(function(e) {
		e.stopPropagation()
	});
	$('#tag-cloud a').live('click',function(e){
		e.preventDefault();
		var k = $('#sidebar textarea').val(),
			m = $(this).text(),
			p = " ";
		$(this).addClass('selected');
		$('#sidebar textarea').val(k+m+p);
		return false;
	});
	$('#upload-images > span').click(function() {
		if ($(this).hasClass('selected')) return;
		var n = $('#upload-images > span').index($(this));
		$('#upload-images > span').removeClass('selected');
		$(this).addClass('selected');
		$('#upload-images>ul>li').hide().eq(n).show()
	});
	$('.ajaxupload_upload').each(function() {
		var clickedObject = $(this),
		$box = '',
		clickedID = $(this).attr('id'),
		clickedParent = $('#' + clickedID).parent();
		new AjaxUpload(clickedID, {
			action: wp_url,
			name: clickedID,
			data: {
				action: 'b_ajax_post_action',
				type: 'upload',
				data: clickedID
			},
			onChange: function(file, extension) {
				if (extension == 'png' || extension == 'PNG' || extension == 'jpg' || extension == 'JPG' || extension == 'jpeg' || extension == 'gif' || extension == 'ico') {
					return true
				} else {
					$('.wizyp_error', clickedParent).remove();
					clickedParent.append('<div class="wizyp_error">Only image files are allowed e.g png, jpeg.</div>');
					return false
				}
			},
			onSubmit: function(file, extension) {
				$('.wizyp_error', clickedParent).fadeOut(200,
				function() {
					$(this).remove()
				});
				$box = $('<li><img src="" /><div class="upload-ajax-loader"></div></li>').appendTo($('#img-preview>ul'))
			},
			onComplete: function(file, response) {
				this.enable();
				$box.find('img').attr('src', response);
				$box.find('div').hide();
				$('#text-desc', clickedParent).val($('#text-desc', clickedParent).val() + '<a href="' + response + '"><img src="' + response + '" /></a>');
				$box = ''
			}
		})
	});
	$('#upload-images>ul>li.next-way>a').click(function(e) {
		if ($('#upload-images>ul>li.next-way>input').val() == '') {
			$('#upload-images>ul>li.next-way>p').show();
			setTimeout(function() {
				$('#upload-images>ul>li.next-way>p').hide()
			},
			2000)
		} else {
			var img_url = $('#upload-images>ul>li.next-way>input').val(),
			$box = $('<li><img src="" /></li>').appendTo($('#img-preview>ul'));
			$box.find('img').attr('src', img_url);
			$('#text-desc').val($('#text-desc').val() + '<a href="' + img_url + '"><img src="' + img_url + '" /></a>');
			$('#upload-images>ul>li.next-way>input').val('');
			hideImages()
		}
		return false
	})
});