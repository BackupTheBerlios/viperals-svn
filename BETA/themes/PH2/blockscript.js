function GetCookie(name) {
  var arg=name+"=";
  var alen = arg.length;
  var clen = document.cookie.length;
  var i  = 0;
  while (i < clen) {
    var j = i + alen;
    if (document.cookie.substring(i,j) == arg)
      return getCookieVal (j);
    i = document.cookie.indexOf(" ", i) + 1;
    if (i == 0) break;
  }
  return null;
}
function getCookieVal (offset) {
   var endstr = document.cookie.indexOf (";", offset);
     if (endstr == 1)
       endstr = document.cookie.length;
     return unescape(document.cookie.substring(offset, endstr));
}
function SetCookie (name, value, expires) {
  var exp = new Date();
  var expiro = (exp.getTime() + (24 * 60 * 60 * 1000 * expires));
  exp.setTime(expiro);
  var expstr = "; expires=" + exp.toGMTString();
  document.cookie = name + "=" + escape(value) + expstr + "; path=/;";
  //  "domain=webmonkey.com;";
}
function DeleteCookie(name){
  if (GetCookie(name)) {
    document.cookie = name + "=:; expires = Thu, 01-Jan-70 00:00:01 GMT; path=/;";
  }
}
var imagepath = "images/";

var hiddenblocks = new Array();
var blocks = GetCookie('hiddenblocks');
if (blocks != null) {
  var hidden = blocks.split(":");
  for (var loop = 0; loop < hidden.length; loop++) {
    var hiddenblock = hidden[loop];
    hiddenblocks[hiddenblock] = hiddenblock;
  }
}

function blockswitch(bid, min, max) {
  var bpe  = document.getElementById('pe'+bid);
  var bph  = document.getElementById('ph'+bid);
  var bico = document.getElementById('pic'+bid);
  if (bpe.style.display=="none") {
    if (bph) { bph.style.display="none"; }
    bpe.style.display="";
    hiddenblocks[bid] = null;
		if (min) {
		bico.src = min;
		} else {
		bico.src = imagepath+"minus.gif";
		}
  } else {
    if (bph) { bph.style.display=""; }
    bpe.style.display="none";
    hiddenblocks[bid] = bid;
        if (max) {
		bico.src = max;
		} else {
		bico.src = imagepath+"plus.gif";
		}
  }
  var cookie = null;
  for (var q = 0; q < hiddenblocks.length; q++) {
    if (hiddenblocks[q] != null) {
      if (cookie != null) {
        cookie = (cookie+":"+hiddenblocks[q]);
      } else {
        cookie = hiddenblocks[q];
      }
    }
  }
  if (cookie != null) {
    SetCookie('hiddenblocks', cookie, 365);
  } else {
    DeleteCookie('hiddenblocks');
  }
}