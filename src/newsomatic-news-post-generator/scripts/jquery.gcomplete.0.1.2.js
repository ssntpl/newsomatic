/**
 * jQuery gComplete v0.1.2 - autocomplete using "Google Web API"
 *
 * Terms of Use - jQuery gComplete
 * under the MIT (http://www.opensource.org/licenses/mit-license.php) License.
 *
 * Copyright 2010 xlune.com All rights reserved.
 * (http://blog.xlune.com/2010/02/jquerygcomplete.html)
 */
 "use strict";
(function ($)
{
	$.fn.extend({
		gcomplete: function(option)
		{
			var _self = $(this);
			
			 if(_self.length == 0 ){
				 return;
			 };
			
			var _tag = _self.get(0).tagName.toLowerCase();
			if(_tag == "input" && _self.attr("type") == "text")
			{
				var _opt,_win,_intval;
				var _prefix = "gcomplete-";
				var def_option = {
					style: "default",
					url: "//suggestqueries.google.com/complete/search",
					query_key: "q",
					param: {
						output: "json",
						client:"firefox"
					},
					limit: 10,
					pan: '#query_string',
					cycle: 500,
					effect: false,
					oneword: false,
					callbackUseOnlyString: false,
					parseFunc: function(result)
					{
						return result[1];
					}
				};

				function cycleStart()
				{
					_intval = setInterval(cycleEngine, getOption('cycle'));
					if($.browser !== undefined)
					{
						$.browser.mozilla ? _self.keypress(keyPress) : _self.keydown(keyPress);
					}
					else
					{
						_self.keydown(keyPress);
					}
					if(typeof(_self.mousewheel)=="function")
					{
						_self.mousedown(mouseDown);
						$(window).mousewheel(mouseWheel);
					}
				};
				function cycleStop()
				{
					clearInterval(_intval);
					_opt.param[_opt.query_key] = "";
					hideWindow();
					if($.browser !== undefined)
					{
						$.browser.mozilla ? _self.unbind("keypress", keyPress) : _self.unbind("keydown", keyPress);
					}
					else
					{
						_self.unbind("keydown", keyPress);
					}
					if(typeof(_self.mousewheel)=="function")
					{
						_self.unbind("mousedown", mouseDown);
						$(window).unbind("mousewheel", mouseWheel);
					}
				};
				function cycleEngine()
				{
					var str = _self.val();
					if(str.length > 0 && str.replace(/ +$/, "") != _opt.param[_opt.query_key].replace(/ +$/, ""))
					{
						request(str);
					}
					else if(str.length <= 0)
					{
						hideWindow();
					}
				};
				function request(query)
				{
					var param = {};
					_opt.param[_opt.query_key] = query;
					for(var i in _opt.param)
					{
						param[i] = _opt.param[i];
					}
					if(getOption('oneword'))
					{
						param[_opt.query_key] = query.split(" ").pop();
						if(!param[_opt.query_key])
						{
							hideWindow();
							return false;
						}
					}

					if(getOption("callbackUseOnlyString"))
					{
						var script = $("#__gcompleteaccess");
						var key = "gcompletef"+getUniqueString();
						param['callback'] = key;
						window[key] = callBack;
						if(script.length > 0)
						{
							script.attr("src", _opt.url + "?" + $.param(param));
						}
						else
						{
							$("<script />")
								.attr("type", "text/javascript")
								.attr("id", "__gcompleteaccess")
								.attr("src", _opt.url + "?" + $.param(param))
								.appendTo("body");
						}
					}
					else
					{
						$.get(
							_opt.url,
							param,
							callBack,
							'jsonp'
						);
					}
				};
				function getUniqueString()
				{
					var arr = [];
					var list = "abcdefghij".split("");
					var time = (new Date()).getTime().toString().split("");
					arr.push(list[Math.floor(Math.random()*list.length)]);
					for(var i=0,imax=time.length; i<imax; i++)
					{
						arr.push(list[time[i]]);
					}
					return arr.join("");
				};
				function callBack(data)
				{
					try {
						var list = getOption("parseFunc")(data);
						if(list.length > 0)
						{
							showWindow(list);
						}
						else
						{
							hideWindow();
						}
					} catch(e) {
						cycleStop();
					}
				};
				function showWindow(list)
				{
					if(list)
					{
						_win.empty();
						var dl,numMax=getOption("limit");
						for(var i=0,imax=Math.min(list.length, numMax); i<imax; i++)
						{
							dl = $('<dl />').appendTo(_win);
							if(typeof(list[i])=="string")
							{
								dl.append($("<dt />").text(list[i]));
								dl.data("text", list[i]);
							}
							else
							{
								dl.append($("<dt />").text(list[i][0]));
								dl.append($("<dd />").text(list[i][1]));
								dl.data("text", list[i][0]);
							}
							dl.hover(
								function(e){
									focusList(this);
								},
								function(e){
									blurList(this);
								}
							)
							.mousedown(function(){
								focusList(this);
								selectList();
							});
						}
					}
					if(getOption('effect') && _win.css("display") == "none")
					{
						_win.fadeIn(300);
					}
					else
					{
						_win.show();
					}
				};
				function hideWindow()
				{
					if(getOption('effect'))
					{
						_win.fadeOut(100);
					}
					else
					{
						_win.hide();
					}
				};
				function focusList(item)
				{
					blurList(_win.find("dl"));
					$(item)
						.addClass("over")
						.attr("rel", "select");
				};
				function blurList(item)
				{
					$(item)
						.removeClass("over")
						.removeAttr('rel');
				};
				function selectList()
				{
					var s = _win.find("dl[rel=select]");
					if(s.length)
					{
						if(getOption('oneword'))
						{
							var words = _self.val().split(' ');
							words.pop();
							words.push(s.data("text"));
							words.push("");
							_self.val(words.join(' '));
						}
						else
						{
							if(s.data("text").indexOf(' ') >= 0)
							{
								$(getOption("pan")).val('"' + s.data("text") + '"');
							}
							else
							{
								$(getOption("pan")).val(s.data("text"));
							}
						}
						return true;
					}
					return false;
				};
				function initOption(option)
				{
					_opt = option || {};
					for(var i in def_option)
					{
						if(!_opt.hasOwnProperty(i))
						{
							_opt[i] = def_option[i];
						}
					}
					_opt.param[_opt.query_key] = "";
					_self.data("_gcomp", _opt);
				};
				function initWindow()
				{
					_win = $('<div />')
						.addClass(_prefix+getOption("style")+"-box")
						.css({
							position: "absolute",
							'z-index': "9999"
						})
						.insertAfter(_self);
					_win.hide();
				};
				function getLeftPos()
				{
					return _self.position().left;
				};
				function getTopPos()
				{
					var tp = _self.position().top + _self.height();
					tp += str2num(_self.css("margin-top"))
						+str2num(_self.css("padding-top"))
						+str2num(_self.css("border-top-width"))
						+str2num(_self.css("padding-bottom"))
						+str2num(_self.css("border-bottom-width"));
					return tp;
				};
				function str2num(str)
				{
					var num = Number(str.replace('px', ''));
					return isNaN(str) ? 1 : num ;
				};
				function getOption(key)
				{
					if(_opt.hasOwnProperty(key))
					{
						return _opt[key];
					}
					return null;
				};
				function mouseDown(e)
				{
					switch(e.button)
					{
						case 1:
							if(!$.browser.msie && selectList())
							{
								return false;
							}
							break;
						case 4:
							if($.browser.msie && selectList())
							{
								return false;
							}
							break;
						default:
							break;
					}
					return true;
				};
				function keyPress(e)
				{
					var s = _win.find("dl[rel=select]");
					switch(e.keyCode)
					{
						case 27:
							cycleStop();
							break;
						case 38:
							focusList(s.prev().length ? s.prev() : _win.find("dl:last"));
							return false;
							break;
						case 40:
							focusList(s.next().length ? s.next() : _win.find("dl:first"));
							return false;
							break;
						case 13:
							if(selectList())
							{
								return false;
							}
							break;
						default:
							break;
					};
					return true;
				};
				function mouseWheel(e, delta)
				{
					if(_win.find("dl").length)
					{
						var s = _win.find("dl[rel=select]");
						var type = delta < 0;
						if(s.length)
						{
							focusList(type
								? (s.next().length ? s.next() : _win.find("dl:first"))
								: (s.prev().length ? s.prev() : _win.find("dl:last"))
							);
						}
						else
						{
							focusList(type
								? _win.find("dl:first")
								: _win.find("dl:last")
							);
						}
					}
					return false;
				};

				initOption(option);
				initWindow();

				_self.focus(function(){
					cycleStart();
				});
				_self.blur(function(){
					cycleStop();
				});
			}

			return _self;
		}
	});
})(jQuery);

