var winLocation = window.location;
var page = winLocation.pathname;
var domain = winLocation.host;
var title = document.title;
var ip = ip||'';
var _AgentInfo = {
    deviceType: "",  // pc or mobile
    OSname: "",         // windows, Android, linux and so on...
    browserName: "",    //  chrome, safari, firefox, IE and so on...
    browserVer: "",   //  browser version， important if in IE environment.
    adaptType: 0,           // A type value, Adapt to the screen due to width
    _init: function(){
        _AgentInfo.setDeviceAndOS();
        _AgentInfo.setBrowser();
    },
    setDeviceAndOS: function(){
        var name = "unknown";
        if(window.navigator.userAgent.indexOf("Android") != -1){
            name = "Android";
        }else if(window.navigator.userAgent.indexOf("iPhone") != -1){
            name = "iPhone";
        }else if(window.navigator.userAgent.indexOf("SymbianOS") != -1){
            name = "SymbianOS";
        }else if(window.navigator.userAgent.indexOf("Windows Phone") != -1){
            name = "Windows Phone";
        }else if(window.navigator.userAgent.indexOf("iPad") != -1){
            name = "iPad";
        }else if(window.navigator.userAgent.indexOf("iPod") != -1){
            name = "iPod";
        }
        if(name != "unknown"){
            _AgentInfo.OSname = name;
            _AgentInfo.deviceType = "mobile";
            return;
        }
        if (window.navigator.userAgent.indexOf("Windows NT 10.0")!= -1){
            name="Windows 10";
        }else if (window.navigator.userAgent.indexOf("Windows NT 6.2") != -1){
            name="Windows 8";
        }else if (window.navigator.userAgent.indexOf("Windows NT 6.1") != -1){
            name="Windows 7";
        }else if (window.navigator.userAgent.indexOf("Windows NT 6.0") != -1){
            name="Windows Vista";
        }else if (window.navigator.userAgent.indexOf("Windows NT 5.1") != -1){
            name="Windows XP";
        }else if (window.navigator.userAgent.indexOf("Windows NT 5.0") != -1){
            name="Windows 2000";
        }else if (window.navigator.userAgent.indexOf("Mac") != -1){
            name="Mac/iOS";
        }else if (window.navigator.userAgent.indexOf("X11") != -1){
            name="UNIX";
        }else if (window.navigator.userAgent.indexOf("Linux") != -1){
            name="Linux";
        }
        _AgentInfo.OSname = name;
        _AgentInfo.deviceType = "pc";
    },
    setBrowser: function(){
        var nAgt = navigator.userAgent;
        var browserName  = navigator.appName;
        var fullVersion  = ''+parseFloat(navigator.appVersion);
        var majorVersion = parseInt(navigator.appVersion,10);
        var nameOffset,verOffset,ix;
        if ((verOffset=nAgt.indexOf("Opera"))!=-1) { // In Opera, the true version is after "Opera" or after "Version"
            browserName = "Opera";
            fullVersion = nAgt.substring(verOffset+6);
            if ((verOffset=nAgt.indexOf("Version"))!=-1)
                fullVersion = nAgt.substring(verOffset+8);
        }
        else if ( (nAgt.indexOf("Trident"))!=-1 ) {   // ( ver >= ie7) In MSIE, the true version is after "MSIE" in userAgent
            if((verOffset=nAgt.indexOf("MSIE"))!=-1){
                fullVersion = nAgt.substring(verOffset+5);
            }else {
                fullVersion = '11.0';
            }
            if(fullVersion == 5){
                fullVersion = "11.0";
            }
            browserName = "IE";
        }
        else if ((verOffset=nAgt.indexOf("Chrome"))!=-1) {  // In Chrome, the true version is after "Chrome"
            browserName = "Chrome";
            fullVersion = nAgt.substring(verOffset+7);
        }
        else if ((verOffset=nAgt.indexOf("Safari"))!=-1) {   // In Safari, the true version is after "Safari" or after "Version"
            browserName = "Safari";
            fullVersion = nAgt.substring(verOffset+7);
            if ((verOffset=nAgt.indexOf("Version"))!=-1)
                fullVersion = nAgt.substring(verOffset+8);
        }
        else if ((verOffset=nAgt.indexOf("Firefox"))!=-1) {    // In Firefox, the true version is after "Firefox"
            browserName = "Firefox";
            fullVersion = nAgt.substring(verOffset+8);
        }
        else if ( (nameOffset=nAgt.lastIndexOf(' ')+1) < (verOffset=nAgt.lastIndexOf('/')) ){   // In most other browsers, "name/version" is at the end of userAgent
            browserName = nAgt.substring(nameOffset,verOffset);
            fullVersion = nAgt.substring(verOffset+1);
            if (browserName.toLowerCase()==browserName.toUpperCase()) {
                browserName = navigator.appName;
            }
        }
        if ((ix=fullVersion.indexOf(";"))!=-1)        // trim the fullVersion string at semicolon/space if present
            fullVersion=fullVersion.substring(0,ix);
        if ((ix=fullVersion.indexOf(" "))!=-1)
            fullVersion=fullVersion.substring(0,ix);
        majorVersion = parseInt(''+fullVersion,10);
        if (isNaN(majorVersion)) {
            fullVersion  = ''+parseFloat(navigator.appVersion);
            majorVersion = parseInt(navigator.appVersion,10);
        }
        _AgentInfo.browserName = browserName;
        _AgentInfo.browserVer = fullVersion;
    },
    isMobile: function(){
        if(_AgentInfo.deviceType == "mobile"){
            return true;
        }
        return false;
    },
    setAdaptType(){     // A type value, Adapt to the screen due to width. For convenient
        if(screen.width <= 374){
            _AgentInfo.adaptType = 0;
        }else if(screen.width <= 413){
            _AgentInfo.adaptType = 1;
        }else {
            _AgentInfo.adaptType = 2;
        }
    }
}
_AgentInfo._init();
var cookie_name = 'site_analysis_cookie';
var imageUrl = imageUrl+'?site='+domain+'&page='+page+'&ip='+ip+'&title='+title+'&token='+token+'&os='+_AgentInfo.OSname+'&device='+_AgentInfo.deviceType+'&browser='+_AgentInfo.browserName;
imageUrl+='&browser_version='+_AgentInfo.browserVer
var cookie = getCookie(cookie_name);
console.log(cookie);
if (cookie==null){
    cookie = uuid();
    setCookie(cookie_name,cookie,1,domain);
}
imageUrl += '&cookie='+cookie;

loadimage(imageUrl);
function loadimage(src) {
    var image = new Image();
    image.onload = function () {
    };
    image.onerror = function () {
        image.src = src + '&CSEND=onerror';
        image.onerror= null;
    };
    image.src=src;
}
function setCookie(name,value,day,domain)
{
    var exp = new Date();
    exp.setTime(exp.getTime() + day*24*60*60*1000);
    document.cookie = name + "="+ escape (value) + ";expires=" + exp.toGMTString()+';domain='+domain+';path=/;';
}

//读取cookies
function getCookie(c_name) {
    if (document.cookie.length > 0)     {
        c_start = document.cookie.indexOf(c_name + "=")
        console.log(document.cookie);
        if (c_start != -1){
            c_start = c_start + c_name.length + 1
            c_end = document.cookie.indexOf(";", c_start)
            if (c_end == -1)
                c_end = document.cookie.length
            return unescape(document.cookie.substring(c_start, c_end))
        }
    }
    return null
}
//删除cookies
function delCookie(name)
{
    var exp = new Date();
    exp.setTime(exp.getTime() - 1);
    var cval=getCookie(name);
    if(cval!=null)
        document.cookie= name + "="+cval+";expires="+exp.toGMTString();
}
function uuid() {
    return 'xxxxxxxxxxxx4xxxyxxxxxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
        var r = Math.random() * 16 | 0,
            v = c == 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}